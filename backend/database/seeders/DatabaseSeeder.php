<?php

namespace Database\Seeders;

use App\Enums\MerchantStatus;
use App\Enums\TransactionStatus;
use App\Models\Institution;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SettlementService;
use App\Support\Money;
use Database\Factories\MerchantFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds ~30 days of realistic activity. Transactions are generated
     * chronologically and successful ones credit the wallet; real settlement
     * runs are executed at historical dates (via Carbon::setTestNow), so the
     * seeded data satisfies the same invariant the tests assert:
     * wallet balance = successful net - settled.
     */
    public function run(): void
    {
        // Guarded rather than unconditional: `php artisan migrate --seed` is
        // the documented way to start this app, and running it a second time on
        // an existing database used to die on the unique index for this email.
        // The factory still owns the password, so it stays in one place.
        if (! User::query()->where('email', 'admin@kudi.test')->exists()) {
            User::factory()->create([
                'name' => 'Kudi Admin',
                'email' => 'admin@kudi.test',
            ]);
        }

        $this->call(InstitutionSeeder::class);

        // Everything below is a chronological replay of 30 days. Running it
        // again would append a *second* 30 days rather than reproduce the
        // first, so this seeder is idempotent by declining to duplicate rather
        // than by merging. `migrate:fresh --seed` rebuilds from scratch.
        if (Merchant::query()->exists()) {
            $this->command?->info(
                'Merchant activity is already seeded — skipping. Run `php artisan migrate:fresh --seed` to rebuild it.'
            );

            return;
        }

        $merchants = $this->seedMerchants();

        // Three historical weekly settlement runs, then a final unsettled week.
        $windows = [
            [30, 22, true],
            [21, 15, true],
            [14, 8, true],
            [7, 0, false],
        ];

        foreach ($windows as [$fromDays, $toDays, $settle]) {
            $this->seedTransactions($merchants, $fromDays, $toDays);

            if ($settle) {
                Carbon::setTestNow(now()->subDays($toDays)->setTime(18, 0));
                app(SettlementService::class)->run();
                Carbon::setTestNow();
            }
        }
    }

    /** @return Collection<int, Merchant> */
    private function seedMerchants()
    {
        $institutions = Institution::all();

        $profiles = [
            ['Kwame Mensah', 'Makola Textiles'],
            ['Ama Serwaa', 'Kejetia Fresh Foods'],
            ['Kofi Boateng', 'Accra Tech Hub'],
            ['Abena Owusu', 'Osu Night Market'],
            ['Yaw Darko', 'Kumasi Auto Parts'],
            ['Akosua Asante', 'Labadi Beach Supplies'],
            ['Kojo Antwi', 'Tamale Shea Co.'],
            ['Adwoa Appiah', 'Cape Coast Fisheries'],
            ['Kwabena Ofori', 'Takoradi Hardware'],
            ['Esi Amankwah', 'Adenta Fashion House'],
            ['Nana Yeboah', 'Bolgatanga Baskets'],
            ['Efua Cudjoe', 'Ho Craft Works'],
        ];

        return collect($profiles)->map(function (array $profile, int $index) use ($institutions) {
            [$name, $business] = $profile;

            $merchant = Merchant::create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@'.Str::slug($business, '').'.gh',
                'business_name' => $business,
                'institution_id' => $institution = $institutions->random()->id,
                'account_number' => MerchantFactory::accountNumberFor($institution),
                // A couple of deactivated merchants make the status UI honest.
                'status' => $index >= 10 ? MerchantStatus::Inactive : MerchantStatus::Active,
            ]);

            $merchant->created_at = now()->subDays(random_int(31, 90));
            $merchant->save();

            return $merchant;
        });
    }

    private function seedTransactions($merchants, int $fromDays, int $toDays): void
    {
        for ($day = $fromDays; $day >= $toDays; $day--) {
            $count = random_int(3, 9);

            for ($i = 0; $i < $count; $i++) {
                $merchant = $merchants->random();
                $gross = random_int(500, 850_000); // GH₵5 – GH₵8,500
                $fee = Money::fee($gross);
                $status = $this->weightedStatus($day);
                $createdAt = now()->subDays($day)->setTime(random_int(7, 21), random_int(0, 59));

                $transaction = Transaction::create([
                    'merchant_id' => $merchant->id,
                    'reference' => 'PAY-'.strtoupper(Str::random(8)),
                    'gross_amount' => $gross,
                    'fee_amount' => $fee,
                    'net_amount' => $gross - $fee,
                    'status' => $status,
                ]);

                $transaction->created_at = $createdAt;
                $transaction->updated_at = $createdAt;
                $transaction->save();

                if ($status === TransactionStatus::Successful) {
                    $merchant->wallet->increment('balance', $gross - $fee);
                }
            }
        }
    }

    private function weightedStatus(int $daysAgo): TransactionStatus
    {
        // Older pendings would have resolved by now; keep pendings recent.
        $roll = random_int(1, 100);

        if ($daysAgo <= 2 && $roll <= 25) {
            return TransactionStatus::Pending;
        }

        return $roll <= 8 ? TransactionStatus::Failed : TransactionStatus::Successful;
    }
}
