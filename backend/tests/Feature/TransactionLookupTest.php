<?php

use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;

/**
 * A shared link to a transaction has to work regardless of where that
 * transaction falls in the ledger. Resolving it against the page the client
 * happens to have loaded only works for the first page, which is precisely the
 * case nobody shares.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('finds a transaction by its reference', function () {
    $merchant = Merchant::factory()->create();
    $transaction = Transaction::factory()->for($merchant)->create([
        'reference' => 'PAY-DEEPLINK',
    ]);

    $this->getJson('/api/transactions/PAY-DEEPLINK')
        ->assertOk()
        ->assertJsonPath('data.reference', 'PAY-DEEPLINK')
        ->assertJsonPath('data.id', $transaction->id)
        // The detail view names the merchant and its payout institution, so both
        // have to come back on a cold load rather than only in the list.
        ->assertJsonPath('data.merchant.business_name', $merchant->business_name)
        ->assertJsonStructure(['data' => ['merchant' => ['institution' => ['name']]]]);
});

it('404s on an unknown reference rather than leaking a query', function () {
    $this->getJson('/api/transactions/PAY-NOPE')->assertNotFound();
});

it('requires authentication', function () {
    auth()->logout();

    $this->getJson('/api/transactions/PAY-ANY')->assertUnauthorized();
});
