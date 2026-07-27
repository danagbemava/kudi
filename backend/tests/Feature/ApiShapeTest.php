<?php

use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;

/**
 * An unauthenticated request that does not ask for JSON used to make the auth
 * middleware resolve route('login') — which this API does not define — turning
 * a 401 into a 500 with a stack trace attached.
 */
it('answers an unauthenticated browser request with 401, not a 500', function () {
    $this->get('/api/dashboard', ['Accept' => 'text/html'])->assertStatus(401);
});

it('still answers an unauthenticated JSON request with 401', function () {
    $this->getJson('/api/dashboard')->assertStatus(401);
});

describe('sorting', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    it('orders transactions by a whitelisted column', function () {
        Transaction::factory()->create(['gross_amount' => 100, 'reference' => 'PAY-LOW']);
        Transaction::factory()->create(['gross_amount' => 900_000, 'reference' => 'PAY-HIGH']);

        $ascending = $this->getJson('/api/transactions?sort=gross&direction=asc')->json('data');
        $descending = $this->getJson('/api/transactions?sort=gross&direction=desc')->json('data');

        expect($ascending[0]['reference'])->toBe('PAY-LOW')
            ->and($descending[0]['reference'])->toBe('PAY-HIGH');
    });

    it('orders merchants by wallet balance, which lives on another table', function () {
        $rich = Merchant::factory()->create(['business_name' => 'Rich']);
        $poor = Merchant::factory()->create(['business_name' => 'Poor']);
        $rich->wallet->update(['balance' => 900_000]);
        $poor->wallet->update(['balance' => 10]);

        $data = $this->getJson('/api/merchants?sort=balance&direction=desc')->json('data');

        expect($data[0]['business_name'])->toBe('Rich')
            ->and($data[1]['business_name'])->toBe('Poor');
    });

    it('refuses a column that is not on the whitelist', function () {
        $this->getJson('/api/transactions?sort=password')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort');
    });

    it('keeps every merchant listed when sorting by balance', function () {
        Merchant::factory()->count(3)->create();

        // Left join, not inner: a merchant is a merchant whether or not its
        // wallet row resolves.
        expect($this->getJson('/api/merchants?sort=balance')->json('meta.total'))->toBe(3);
    });
});
