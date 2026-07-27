<?php

use App\Models\User;

/**
 * `from` and `to` reach these endpoints from URLs and bookmarks, not only from
 * the date pickers. Request::date() hands whatever arrives to Carbon, which
 * throws on nonsense — so before this validation existed, a mistyped query
 * parameter was a 500.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

$endpoints = [
    'reports' => '/api/reports/summary',
    'transactions' => '/api/transactions',
    'settlements' => '/api/settlements',
];

foreach ($endpoints as $name => $path) {
    it("rejects an unparseable from date on {$name} instead of failing", function () use ($path) {
        $this->getJson($path.'?from=not-a-date')
            ->assertStatus(422)
            ->assertJsonValidationErrors('from');
    });

    it("rejects an unparseable to date on {$name}", function () use ($path) {
        $this->getJson($path.'?to=13/13/2026')
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');
    });

    it("rejects a reversed range on {$name}", function () use ($path) {
        $this->getJson($path.'?from=2026-07-20&to=2026-07-10')
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');
    });

    it("still accepts an empty range on {$name}", function () use ($path) {
        $this->getJson($path.'?from=&to=')->assertOk();
    });
}

it('rejects a page size it cannot read rather than silently paging by one', function () {
    $this->getJson('/api/transactions?per_page=abc')
        ->assertStatus(422)
        ->assertJsonValidationErrors('per_page');
});

it('rejects an unknown status filter rather than returning nothing', function () {
    $this->getJson('/api/transactions?status=banana')
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});
