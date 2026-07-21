<?php

use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;

/**
 * The scopes are deliberately no-ops when handed nothing, so callers can chain
 * them unconditionally instead of wrapping each in a `when()`. That contract is
 * what these cover.
 */
describe('Merchant scopes', function () {
    it('searches business name, contact name and email', function () {
        Merchant::factory()->create(['business_name' => 'Kejetia Fresh Foods']);
        Merchant::factory()->create(['name' => 'Yaa Asantewaa']);
        Merchant::factory()->create(['email' => 'kojo@tamaleshea.gh']);
        Merchant::factory()->create(['business_name' => 'Unrelated Ltd']);

        expect(Merchant::query()->search('kejetia')->count())->toBe(1)
            ->and(Merchant::query()->search('asantewaa')->count())->toBe(1)
            ->and(Merchant::query()->search('tamaleshea')->count())->toBe(1);
    });

    it('is case-insensitive and ignores surrounding whitespace', function () {
        Merchant::factory()->create(['business_name' => 'Makola Textiles']);

        expect(Merchant::query()->search('  MAKOLA  ')->count())->toBe(1);
    });

    it('does nothing when the term is null or blank', function () {
        Merchant::factory()->count(3)->create();

        expect(Merchant::query()->search(null)->count())->toBe(3)
            ->and(Merchant::query()->search('   ')->count())->toBe(3);
    });

    it('filters by status, and does nothing without one', function () {
        Merchant::factory()->count(2)->create();
        Merchant::factory()->inactive()->create();

        expect(Merchant::query()->withStatus('inactive')->count())->toBe(1)
            ->and(Merchant::query()->withStatus(null)->count())->toBe(3);
    });
});

describe('Transaction scopes', function () {
    it('filters by status, merchant and reference', function () {
        $merchant = Merchant::factory()->create();
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'reference' => 'PAY-ALPHA']);
        Transaction::factory()->pending()->create(['reference' => 'PAY-BETA']);

        expect(Transaction::query()->withStatus('pending')->count())->toBe(1)
            ->and(Transaction::query()->forMerchant($merchant->id)->count())->toBe(1)
            ->and(Transaction::query()->referenceLike('alpha')->count())->toBe(1);
    });

    it('does nothing when handed nothing', function () {
        Transaction::factory()->count(4)->create();

        expect(
            Transaction::query()
                ->withStatus(null)
                ->forMerchant(null)
                ->referenceLike('')
                ->createdBetween(null, null)
                ->count()
        )->toBe(4);
    });

    it('bounds a date range inclusively at both ends', function () {
        Transaction::factory()->create(['created_at' => '2026-07-01 09:00:00']);
        Transaction::factory()->create(['created_at' => '2026-07-10 09:00:00']);
        Transaction::factory()->create(['created_at' => '2026-07-20 09:00:00']);

        $inRange = Transaction::query()
            ->createdBetween(new DateTime('2026-07-01'), new DateTime('2026-07-10'))
            ->count();

        expect($inRange)->toBe(2);
    });
});

describe('Settlement scopes', function () {
    it('bounds settled_at, and does nothing without a range', function () {
        Settlement::factory()->create(['settled_at' => '2026-06-01 18:00:00']);
        Settlement::factory()->create(['settled_at' => '2026-07-15 18:00:00']);

        expect(Settlement::query()->settledBetween(new DateTime('2026-07-01'), null)->count())->toBe(1)
            ->and(Settlement::query()->settledBetween(null, null)->count())->toBe(2);
    });
});
