<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettlementService
{
    /**
     * Settle every positive wallet balance into settlement records.
     *
     * Runs in a single DB transaction with the affected wallet rows locked.
     * Balances are decremented by the settled amount — never overwritten
     * with zero — so a credit that slips in concurrently is preserved for
     * the next run rather than destroyed.
     *
     * @return array{batch_ref: string, merchant_count: int, total_amount: int}|null
     *         null when no wallet holds a positive balance.
     */
    public function run(): ?array
    {
        return DB::transaction(function () {
            $wallets = Wallet::query()
                ->where('balance', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($wallets->isEmpty()) {
                return null;
            }

            $batchRef = $this->generateBatchRef();
            $now = now();
            $total = 0;

            foreach ($wallets as $wallet) {
                $amount = $wallet->balance;

                Settlement::create([
                    'merchant_id' => $wallet->merchant_id,
                    'reference' => $this->generateReference(),
                    'amount' => $amount,
                    'batch_ref' => $batchRef,
                    'settled_at' => $now,
                ]);

                $wallet->decrement('balance', $amount);
                $total += $amount;
            }

            return [
                'batch_ref' => $batchRef,
                'merchant_count' => $wallets->count(),
                'total_amount' => $total,
            ];
        });
    }

    private function generateBatchRef(): string
    {
        return 'BATCH-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
    }

    private function generateReference(): string
    {
        do {
            $reference = 'STL-'.strtoupper(Str::random(8));
        } while (Settlement::where('reference', $reference)->exists());

        return $reference;
    }
}
