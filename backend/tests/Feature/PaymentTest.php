<?php

use App\Enums\TransactionStatus;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Lottery;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('POST /api/payments', function () {
    it('creates a pending transaction with the 1.5% fee breakdown', function () {
        $merchant = Merchant::factory()->create();

        $response = $this->postJson('/api/payments', [
            'merchant_id' => $merchant->id,
            'amount' => 10_000, // GH₵100.00 in pesewas
            'reference' => 'PAY-ORDER-001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.reference', 'PAY-ORDER-001')
            ->assertJsonPath('data.gross_amount', 10_000)
            ->assertJsonPath('data.fee_amount', 150)
            ->assertJsonPath('data.net_amount', 9_850)
            ->assertJsonPath('data.status', 'pending');
    });

    it('does not credit the wallet while the transaction is pending', function () {
        $merchant = Merchant::factory()->create();

        $this->postJson('/api/payments', [
            'merchant_id' => $merchant->id,
            'amount' => 10_000,
            'reference' => 'PAY-ORDER-002',
        ])->assertCreated();

        expect($merchant->wallet->refresh()->balance)->toBe(0);
    });

    it('rejects payments to inactive merchants', function () {
        $merchant = Merchant::factory()->inactive()->create();

        $this->postJson('/api/payments', [
            'merchant_id' => $merchant->id,
            'amount' => 10_000,
            'reference' => 'PAY-ORDER-003',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('merchant_id');
    });

    it('rejects duplicate references', function () {
        $merchant = Merchant::factory()->create();
        Transaction::factory()->create(['reference' => 'PAY-DUP']);

        $this->postJson('/api/payments', [
            'merchant_id' => $merchant->id,
            'amount' => 10_000,
            'reference' => 'PAY-DUP',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reference');
    });

    it('validates required fields and positive integer amount', function () {
        $this->postJson('/api/payments', [
            'amount' => -5,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['merchant_id', 'amount', 'reference']);
    });
});

describe('POST /api/transactions/{transaction}/process', function () {
    it('credits the wallet with the net amount when the gateway succeeds', function () {
        Lottery::alwaysWin();
        $merchant = Merchant::factory()->create();
        $transaction = Transaction::factory()->pending()->create([
            'merchant_id' => $merchant->id,
            'gross_amount' => 10_000,
            'fee_amount' => 150,
            'net_amount' => 9_850,
        ]);

        $this->postJson("/api/transactions/{$transaction->id}/process")
            ->assertOk()
            ->assertJsonPath('data.status', 'successful');

        expect($merchant->wallet->refresh()->balance)->toBe(9_850);
    });

    it('leaves the wallet untouched when the gateway fails', function () {
        Lottery::alwaysLose();
        $merchant = Merchant::factory()->create();
        $transaction = Transaction::factory()->pending()->create([
            'merchant_id' => $merchant->id,
        ]);

        $this->postJson("/api/transactions/{$transaction->id}/process")
            ->assertOk()
            ->assertJsonPath('data.status', 'failed');

        expect($merchant->wallet->refresh()->balance)->toBe(0);
    });

    it('refuses to process a transaction that is not pending', function () {
        $transaction = Transaction::factory()->create([
            'status' => TransactionStatus::Successful,
        ]);

        $this->postJson("/api/transactions/{$transaction->id}/process")
            ->assertUnprocessable();
    });
});

describe('authentication', function () {
    it('rejects unauthenticated payment requests', function () {
        auth()->logout();

        $this->postJson('/api/payments', [])->assertUnauthorized();
    });
});
