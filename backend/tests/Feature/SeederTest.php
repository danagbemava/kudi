<?php

use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

/**
 * `php artisan migrate --seed` is the documented way to start this app. Running
 * it twice used to die on the unique index over users.email, which is a bad
 * first impression for a reviewer following the README.
 */
it('can be run twice without failing', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);
})->throwsNoExceptions();

it('does not duplicate the activity replay on a second run', function () {
    $this->seed(DatabaseSeeder::class);

    $merchants = Merchant::count();
    $transactions = Transaction::count();

    $this->seed(DatabaseSeeder::class);

    // The replay walks 30 days chronologically; running it again would append
    // a second 30 days rather than reproduce the first.
    expect(Merchant::count())->toBe($merchants)
        ->and(Transaction::count())->toBe($transactions)
        ->and(User::where('email', 'admin@kudi.test')->count())->toBe(1);
});

it('leaves the wallet invariant true', function () {
    $this->seed(DatabaseSeeder::class);

    Merchant::with('wallet')->each(function (Merchant $merchant) {
        $credited = $merchant->transactions()->where('status', 'successful')->sum('net_amount');
        $settled = $merchant->settlements()->sum('amount');

        expect($merchant->wallet->balance)->toBe((int) $credited - (int) $settled);
    });
});
