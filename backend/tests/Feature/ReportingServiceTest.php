<?php

use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Services\ReportingService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Freeze time so day bucketing is deterministic.
    Carbon::setTestNow('2026-07-21 12:00:00');
    $this->reporting = app(ReportingService::class);
});

afterEach(fn () => Carbon::setTestNow());

describe('volumeSeries', function () {
    it('returns one zero-filled point per day, quiet days included', function () {
        Transaction::factory()->create([
            'gross_amount' => 10_000, 'fee_amount' => 150, 'net_amount' => 9_850,
            'created_at' => '2026-07-20 09:00:00',
        ]);

        $series = $this->reporting->volumeSeries();

        expect($series)->toHaveCount(30)
            ->and($series[0]['date'])->toBe('2026-06-22')
            ->and($series[29]['date'])->toBe('2026-07-21');

        $byDate = collect($series)->keyBy('date');
        expect($byDate['2026-07-20']['volume'])->toBe(10_000)
            ->and($byDate['2026-07-20']['fees'])->toBe(150)
            // A day with no payments is present and zero, not skipped — otherwise
            // the chart would compress quiet periods and distort the shape.
            ->and($byDate['2026-07-19']['volume'])->toBe(0);
    });

    it('counts only successful payments', function () {
        Transaction::factory()->pending()->create([
            'gross_amount' => 50_000, 'created_at' => '2026-07-20 09:00:00',
        ]);
        Transaction::factory()->failed()->create([
            'gross_amount' => 70_000, 'created_at' => '2026-07-20 10:00:00',
        ]);

        $total = collect($this->reporting->volumeSeries())->sum('volume');

        expect($total)->toBe(0);
    });

    it('excludes payments older than the window', function () {
        Transaction::factory()->create([
            'gross_amount' => 99_000, 'created_at' => '2026-05-01 09:00:00',
        ]);

        expect(collect($this->reporting->volumeSeries())->sum('volume'))->toBe(0);
    });
});

describe('unsettledFloat', function () {
    it('lists only merchants holding float, largest first', function () {
        $big = Merchant::factory()->create(['business_name' => 'Big Float']);
        $small = Merchant::factory()->create(['business_name' => 'Small Float']);
        Merchant::factory()->create(); // settled out, holds nothing

        $big->wallet->update(['balance' => 500_000]);
        $small->wallet->update(['balance' => 25_000]);

        $float = $this->reporting->unsettledFloat();

        expect($float)->toHaveCount(2)
            ->and($float[0]['business_name'])->toBe('Big Float')
            ->and($float[0]['wallet_balance'])->toBe(500_000)
            ->and($float[1]['business_name'])->toBe('Small Float');
    });

    it('caps how many merchants it returns', function () {
        Merchant::factory()->count(5)->create()
            ->each(fn ($m) => $m->wallet->update(['balance' => 1_000]));

        expect($this->reporting->unsettledFloat(limit: 3))->toHaveCount(3);
    });
});

describe('summary', function () {
    it('totals fees, volume and settlements for the period', function () {
        Transaction::factory()->create([
            'gross_amount' => 10_000, 'fee_amount' => 150, 'net_amount' => 9_850,
            'created_at' => '2026-07-15 09:00:00',
        ]);
        Settlement::factory()->create([
            'amount' => 40_000, 'settled_at' => '2026-07-15 18:00:00',
        ]);

        $summary = $this->reporting->summary(null, null);

        expect($summary['platform_fees'])->toBe(150)
            ->and($summary['successful_volume'])->toBe(10_000)
            ->and($summary['total_settled'])->toBe(40_000);
    });

    it('honours the date range on both transactions and settlements', function () {
        Transaction::factory()->create(['fee_amount' => 111, 'created_at' => '2026-06-01 09:00:00']);
        Transaction::factory()->create(['fee_amount' => 222, 'created_at' => '2026-07-15 09:00:00']);
        Settlement::factory()->create(['amount' => 900, 'settled_at' => '2026-06-01 18:00:00']);
        Settlement::factory()->create(['amount' => 800, 'settled_at' => '2026-07-15 18:00:00']);

        $summary = $this->reporting->summary(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-21'),
        );

        expect($summary['platform_fees'])->toBe(222)
            ->and($summary['total_settled'])->toBe(800);
    });

    it('counts each transaction outcome separately', function () {
        Transaction::factory()->count(3)->create();
        Transaction::factory()->pending()->count(2)->create();
        Transaction::factory()->failed()->create();

        $counts = $this->reporting->summary(null, null)['transaction_counts'];

        expect($counts)->toBe(['successful' => 3, 'pending' => 2, 'failed' => 1]);
    });
});
