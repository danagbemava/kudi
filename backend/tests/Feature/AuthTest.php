<?php

use App\Models\User;

describe('session auth', function () {
    it('logs in with valid credentials and establishes a session', function () {
        User::factory()->create(['email' => 'admin@kudi.test']);

        $this->postJson('/api/login', [
            'email' => 'admin@kudi.test',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.email', 'admin@kudi.test');

        $this->assertAuthenticated();
        $this->getJson('/api/dashboard')->assertOk();
    });

    it('rejects invalid credentials with a field error', function () {
        User::factory()->create(['email' => 'admin@kudi.test']);

        $this->postJson('/api/login', [
            'email' => 'admin@kudi.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    });

    it('logs out and invalidates the session', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/logout')->assertNoContent();

        $this->assertGuest();
    });

    it('blocks every api route for guests', function () {
        foreach (['/api/dashboard', '/api/merchants', '/api/transactions', '/api/settlements'] as $route) {
            $this->getJson($route)->assertUnauthorized();
        }
    });
});
