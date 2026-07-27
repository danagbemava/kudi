<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    RateLimiter::clear(strtolower('admin@kudi.test').'|127.0.0.1');

    User::factory()->create([
        'email' => 'admin@kudi.test',
        'password' => bcrypt('password'),
    ]);
});

function attempt(string $password): TestResponse
{
    return test()->postJson('/api/login', [
        'email' => 'admin@kudi.test',
        'password' => $password,
    ]);
}

it('locks an email and IP out after five failed attempts', function () {
    foreach (range(1, 5) as $ignored) {
        attempt('wrong')->assertStatus(422);
    }

    $response = attempt('wrong')->assertStatus(429);

    expect($response->json('message'))->toContain('Too many sign-in attempts')
        ->and($response->headers->get('Retry-After'))->not->toBeNull();
});

/**
 * The lockout must not outlast the mistake that caused it: someone who fumbles
 * a password twice and then remembers it should sign in, not wait out a minute
 * they earned by getting it right.
 */
it('forgets failed attempts once one succeeds', function () {
    attempt('wrong')->assertStatus(422);
    attempt('wrong')->assertStatus(422);

    attempt('password')->assertOk();

    auth()->logout();

    // A fresh budget: four more failures would have tripped the old tally.
    foreach (range(1, 4) as $ignored) {
        attempt('wrong')->assertStatus(422);
    }
});

it('refuses the correct password too while locked out', function () {
    foreach (range(1, 5) as $ignored) {
        attempt('wrong')->assertStatus(422);
    }

    // Otherwise the limit is trivially bypassed by guessing correctly, which
    // is exactly what an attacker is trying to do.
    attempt('password')->assertStatus(429);
});

it('answers an already-signed-in caller in JSON rather than redirecting', function () {
    $user = User::first();

    $this->actingAs($user)
        ->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});
