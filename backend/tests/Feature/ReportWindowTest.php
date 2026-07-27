<?php

use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportingService;

beforeEach(function () {
    $this->reporting = app(ReportingService::class);
    $this->actingAs(User::factory()->create());
});

/**
 * The reports screen puts period totals directly above a chart. If the chart
 * silently covers a different span than the totals, both are believable and one
 * is wrong — so the window the series actually covers is part of the response.
 */
it('reports the window the series covers', function () {
    $summary = $this->reporting->summary(
        new DateTime('2026-07-01'),
        new DateTime('2026-07-10'),
    );

    expect($summary['series_window'])
        ->toMatchArray(['from' => '2026-07-01', 'to' => '2026-07-10', 'days' => 10, 'truncated' => false])
        ->and($summary['series'])->toHaveCount(10);
});

it('says so when a long range is trimmed to fit', function () {
    $summary = $this->reporting->summary(
        new DateTime('2024-01-01'),
        new DateTime('2026-07-27'),
    );

    expect($summary['series_window']['truncated'])->toBeTrue()
        ->and($summary['series_window']['days'])->toBe(180)
        ->and($summary['series'])->toHaveCount(180)
        // The trimmed window keeps the most recent end, not the oldest.
        ->and($summary['series_window']['to'])->toBe('2026-07-27');
});

/**
 * "All time" leaves both ends unset. The totals then cover the whole ledger, so
 * the chart has to start at the first payment rather than at an arbitrary 30
 * days back, or the two disagree with nothing on screen admitting it.
 */
it('charts from the first payment when no range is given', function () {
    Transaction::factory()->create(['created_at' => now()->subDays(90)]);
    Transaction::factory()->create(['created_at' => now()->subDays(2)]);

    $summary = $this->reporting->summary(null, null);

    expect($summary['series_window']['from'])->toBe(now()->subDays(90)->toDateString())
        ->and($summary['series_window']['to'])->toBe(now()->toDateString())
        ->and($summary['series'])->toHaveCount(91);
});

it('falls back to a 30 day window on an empty ledger', function () {
    $summary = $this->reporting->summary(null, null);

    expect($summary['series'])->toHaveCount(30)
        ->and($summary['series_window']['truncated'])->toBeFalse();
});

/**
 * Carbon 3's diffInDays is signed. Called directly with the ends the wrong way
 * round, the day count went negative and the series ran backwards through time.
 */
it('orders a reversed range rather than running time backwards', function () {
    $summary = $this->reporting->summary(
        new DateTime('2026-07-20'),
        new DateTime('2026-07-10'),
    );

    $dates = array_column($summary['series'], 'date');

    expect($dates)->toHaveCount(11)
        ->and($dates[0])->toBe('2026-07-10')
        ->and(end($dates))->toBe('2026-07-20')
        ->and($dates)->toBe(collect($dates)->sort()->values()->all());
});

it('exposes the fee rate so the client cannot drift from it', function () {
    config(['kudi.fee_basis_points' => 275]);

    $this->getJson('/api/config')
        ->assertOk()
        ->assertJsonPath('data.fee_basis_points', 275)
        ->assertJsonPath('data.currency', 'GHS');
});
