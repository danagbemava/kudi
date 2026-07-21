<?php

use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('GET /api/transactions', function () {
    it('lists transactions newest first with merchant info and pagination meta', function () {
        Transaction::factory()->count(3)->create();

        $this->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'reference', 'gross_amount', 'fee_amount', 'net_amount', 'status', 'merchant' => ['business_name']]],
                'meta' => ['total', 'current_page'],
            ]);
    });

    it('filters by status, merchant and reference search', function () {
        $merchant = Merchant::factory()->create();
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'reference' => 'PAY-AAA']);
        Transaction::factory()->pending()->create(['reference' => 'PAY-BBB']);

        $this->getJson('/api/transactions?status=pending')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'PAY-BBB');

        $this->getJson("/api/transactions?merchant_id={$merchant->id}")
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'PAY-AAA');

        $this->getJson('/api/transactions?search=bbb')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'PAY-BBB');
    });

    it('filters by date range', function () {
        Transaction::factory()->create(['created_at' => '2026-07-01 10:00:00', 'reference' => 'PAY-OLD']);
        Transaction::factory()->create(['created_at' => '2026-07-20 10:00:00', 'reference' => 'PAY-NEW']);

        $this->getJson('/api/transactions?from=2026-07-10&to=2026-07-21')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'PAY-NEW');
    });
});

describe('GET /api/settlements', function () {
    it('lists settlements with merchant info, date filtering and pagination', function () {
        Settlement::factory()->create(['settled_at' => '2026-07-01 09:00:00']);
        Settlement::factory()->create(['settled_at' => '2026-07-20 09:00:00']);

        $this->getJson('/api/settlements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['reference', 'batch_ref', 'amount', 'settled_at', 'merchant']], 'meta']);

        $this->getJson('/api/settlements?from=2026-07-10')
            ->assertOk()->assertJsonCount(1, 'data');
    });
});

describe('GET /api/dashboard', function () {
    it('returns balance, volume, settlement, fee totals, a volume series and recent transactions', function () {
        $merchant = Merchant::factory()->create();
        $merchant->wallet->update(['balance' => 9_850]);
        Transaction::factory()->create([
            'merchant_id' => $merchant->id,
            'gross_amount' => 10_000, 'fee_amount' => 150, 'net_amount' => 9_850,
        ]);
        Transaction::factory()->pending()->create(['gross_amount' => 7_777]);
        Settlement::factory()->create(['amount' => 55_000]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.wallet_balance', 9_850)
            ->assertJsonPath('data.totals.payment_volume', 10_000) // successful only
            ->assertJsonPath('data.totals.platform_fees', 150)
            ->assertJsonPath('data.totals.settled', 55_000)
            ->assertJsonStructure(['data' => [
                'totals' => ['wallet_balance', 'payment_volume', 'platform_fees', 'settled', 'merchant_count', 'pending_count'],
                'volume_series',
                'recent_transactions',
                'unsettled' => [['id', 'business_name', 'wallet_balance']],
            ]])
            // Only merchants actually holding float appear, largest first.
            ->assertJsonCount(1, 'data.unsettled')
            ->assertJsonPath('data.unsettled.0.wallet_balance', 9_850);
    });
});

describe('GET /api/reports/summary', function () {
    it('returns period totals for fees, volume and settlements', function () {
        Transaction::factory()->create(['gross_amount' => 10_000, 'fee_amount' => 150, 'net_amount' => 9_850]);
        Settlement::factory()->create(['amount' => 20_000]);

        $this->getJson('/api/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.platform_fees', 150)
            ->assertJsonPath('data.successful_volume', 10_000)
            ->assertJsonPath('data.total_settled', 20_000)
            ->assertJsonStructure(['data' => [
                'platform_fees', 'successful_volume', 'total_settled',
                'transaction_counts' => ['successful', 'pending', 'failed'],
            ]]);
    });

    it('respects a date range', function () {
        Transaction::factory()->create(['fee_amount' => 111, 'created_at' => '2026-06-01']);
        Transaction::factory()->create(['fee_amount' => 222, 'created_at' => '2026-07-15']);

        $this->getJson('/api/reports/summary?from=2026-07-01&to=2026-07-21')
            ->assertOk()
            ->assertJsonPath('data.platform_fees', 222);
    });
});
