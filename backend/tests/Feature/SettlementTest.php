<?php

use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Lottery;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('POST /api/settlements/run', function () {
    it('moves every positive wallet balance into a settlement record and empties the wallets', function () {
        [$a, $b] = Merchant::factory()->count(2)->create();
        $a->wallet->update(['balance' => 150_000]);
        $b->wallet->update(['balance' => 42_500]);

        $response = $this->postJson('/api/settlements/run');

        $response->assertOk()
            ->assertJsonPath('data.merchant_count', 2)
            ->assertJsonPath('data.total_amount', 192_500);

        expect($a->wallet->refresh()->balance)->toBe(0)
            ->and($b->wallet->refresh()->balance)->toBe(0)
            ->and(Settlement::where('merchant_id', $a->id)->sole()->amount)->toBe(150_000)
            ->and(Settlement::where('merchant_id', $b->id)->sole()->amount)->toBe(42_500);
    });

    it('groups all settlements from one run under a single batch reference', function () {
        Merchant::factory()->count(3)->create()
            ->each(fn ($m) => $m->wallet->update(['balance' => 10_000]));

        $this->postJson('/api/settlements/run')->assertOk();

        expect(Settlement::query()->distinct('batch_ref')->count())->toBe(1)
            ->and(Settlement::count())->toBe(3);
    });

    it('skips wallets with zero balance', function () {
        $funded = Merchant::factory()->create();
        $funded->wallet->update(['balance' => 5_000]);
        $empty = Merchant::factory()->create();

        $this->postJson('/api/settlements/run')->assertOk();

        expect(Settlement::count())->toBe(1)
            ->and(Settlement::sole()->merchant_id)->toBe($funded->id);
    });

    it('settles inactive merchants with positive balances', function () {
        $inactive = Merchant::factory()->inactive()->create();
        $inactive->wallet->update(['balance' => 7_500]);

        $this->postJson('/api/settlements/run')->assertOk();

        expect(Settlement::sole()->merchant_id)->toBe($inactive->id)
            ->and($inactive->wallet->refresh()->balance)->toBe(0);
    });

    it('rejects a run when there is nothing to settle', function () {
        Merchant::factory()->create(); // zero balance

        $this->postJson('/api/settlements/run')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'There are no positive wallet balances to settle.');

        expect(Settlement::count())->toBe(0);
    });

    it('preserves the wallet invariant: balance equals successful net minus settled', function () {
        Lottery::alwaysWin();
        $merchant = Merchant::factory()->create();

        // Two successful payments…
        foreach ([['PAY-A', 10_000], ['PAY-B', 20_000]] as [$ref, $amount]) {
            $tx = $this->postJson('/api/payments', [
                'merchant_id' => $merchant->id,
                'amount' => $amount,
                'reference' => $ref,
            ])->json('data.id');
            $this->postJson("/api/transactions/{$tx}/process")->assertOk();
        }

        // …then a settlement, then one more successful payment.
        $this->postJson('/api/settlements/run')->assertOk();

        $tx = $this->postJson('/api/payments', [
            'merchant_id' => $merchant->id,
            'amount' => 5_000,
            'reference' => 'PAY-C',
        ])->json('data.id');
        $this->postJson("/api/transactions/{$tx}/process")->assertOk();

        $successfulNet = Transaction::where('merchant_id', $merchant->id)
            ->where('status', 'successful')->sum('net_amount');
        $settled = Settlement::where('merchant_id', $merchant->id)->sum('amount');

        expect($merchant->wallet->refresh()->balance)
            ->toBe((int) $successfulNet - (int) $settled)
            ->toBe(4_925); // net of GH₵50.00 payment
    });
});
