<?php

use App\Models\Institution;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('POST /api/merchants', function () {
    it('registers a merchant and auto-creates an empty wallet', function () {
        $institution = Institution::factory()->create();

        $response = $this->postJson('/api/merchants', [
            'name' => 'Ama Serwaa',
            'email' => 'ama@serwaa.gh',
            'business_name' => 'Serwaa Trading',
            'institution_id' => $institution->id,
            'account_number' => '0244123456',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.business_name', 'Serwaa Trading')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.wallet_balance', 0);
    });

    it('rejects duplicate emails and unknown institutions', function () {
        Merchant::factory()->create(['email' => 'taken@kudi.gh']);

        $this->postJson('/api/merchants', [
            'name' => 'X',
            'email' => 'taken@kudi.gh',
            'business_name' => 'X Ltd',
            'institution_id' => 999,
            'account_number' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'institution_id']);
    });

    it('requires all registration fields', function () {
        $this->postJson('/api/merchants', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name', 'email', 'business_name', 'institution_id', 'account_number',
            ]);
    });
});

describe('GET /api/merchants', function () {
    it('lists merchants with wallet balances, newest first', function () {
        Merchant::factory()->count(3)->create();

        $this->getJson('/api/merchants')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'business_name', 'status', 'wallet_balance']], 'meta']);
    });

    it('filters by search term across business name, contact name and email', function () {
        Merchant::factory()->create(['business_name' => 'Kejetia Ventures']);
        Merchant::factory()->create(['business_name' => 'Osu Foods']);

        $this->getJson('/api/merchants?search=kejetia')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.business_name', 'Kejetia Ventures');
    });

    it('filters by status', function () {
        Merchant::factory()->create();
        Merchant::factory()->inactive()->create(['business_name' => 'Dormant Ltd']);

        $this->getJson('/api/merchants?status=inactive')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.business_name', 'Dormant Ltd');
    });
});

describe('GET /api/merchants/{merchant}', function () {
    it('returns profile, wallet, recent transactions and settlements with stats', function () {
        $merchant = Merchant::factory()->create();
        $merchant->wallet->update(['balance' => 9_850]);
        Transaction::factory()->count(2)->create(['merchant_id' => $merchant->id]);
        Settlement::factory()->create(['merchant_id' => $merchant->id, 'amount' => 50_000]);

        $this->getJson("/api/merchants/{$merchant->id}")
            ->assertOk()
            ->assertJsonPath('data.wallet_balance', 9_850)
            ->assertJsonCount(2, 'data.recent_transactions')
            ->assertJsonCount(1, 'data.recent_settlements')
            ->assertJsonPath('data.stats.total_settled', 50_000)
            ->assertJsonStructure(['data' => ['stats' => ['total_settled', 'transaction_count', 'successful_volume']]]);
    });
});

describe('PATCH /api/merchants/{merchant}/status', function () {
    it('deactivates and reactivates a merchant', function () {
        $merchant = Merchant::factory()->create();

        $this->patchJson("/api/merchants/{$merchant->id}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->patchJson("/api/merchants/{$merchant->id}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    });

    it('rejects unknown statuses', function () {
        $merchant = Merchant::factory()->create();

        $this->patchJson("/api/merchants/{$merchant->id}/status", ['status' => 'suspended'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    });
});

describe('GET /api/institutions', function () {
    it('lists institutions for the registration form', function () {
        Institution::factory()->count(2)->create();
        Institution::factory()->mobileMoney()->create(['name' => 'MTN Mobile Money', 'code' => 'MTN']);

        $this->getJson('/api/institutions')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });
});
