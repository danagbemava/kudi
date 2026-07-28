<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Read-side aggregation for the dashboard and reports.
 *
 * Separate from the controllers because this is genuine domain logic —
 * date bucketing, zero-filling, float composition — rather than mapping
 * query parameters onto a builder, and because it is worth testing
 * without going through HTTP.
 */
class ReportingService
{
    /** Longest series the reports endpoint will return. */
    private const MAX_SERIES_DAYS = 180;

    /** Headline figures for the dashboard. */
    public function totals(): array
    {
        $successful = Transaction::query()->withStatus(TransactionStatus::Successful->value);
        $pending = Transaction::query()->withStatus(TransactionStatus::Pending->value);

        return [
            'wallet_balance' => (int) Wallet::sum('balance'),
            'payment_volume' => (int) $successful->clone()->sum('gross_amount'),
            'platform_fees' => (int) $successful->clone()->sum('fee_amount'),
            'settled' => (int) Settlement::sum('amount'),
            'merchant_count' => Merchant::count(),
            'pending_count' => $pending->clone()->count(),
            // What is still in flight, reported both ways on purpose.
            //
            // `wallet_balance` above is money net of the fee — what actually
            // reached a wallet. Quoting pending money in gross beside it put
            // two different units on one row of cards: a GHS 667.32 payment
            // resolving adds GHS 657.31 to the float, not GHS 667.32, and the
            // dashboard appeared to lose the difference. The net figure is the
            // comparable one and leads; the gross is what the customer was
            // charged, so it is still worth stating.
            'pending_amount' => (int) $pending->clone()->sum('gross_amount'),
            'pending_net_amount' => (int) $pending->clone()->sum('net_amount'),
        ];
    }

    /**
     * Daily successful volume, zero-filled so the chart has a point per day
     * rather than skipping quiet ones and distorting the shape.
     *
     * @return array<int, array{date: string, volume: int, fees: int}>
     */
    public function volumeSeries(int $days = 30): array
    {
        return $this->dailySeries(now()->subDays($days - 1)->startOfDay(), now()->endOfDay());
    }

    /**
     * Daily volume and fees between two dates, zero-filled.
     *
     * @return array<int, array{date: string, volume: int, fees: int}>
     */
    private function dailySeries(DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->dailySeriesWithWindow($from, $to)['series'];
    }

    /**
     * The series plus the window it actually covers.
     *
     * The window is returned rather than assumed because it can differ from
     * the one asked for: a range longer than MAX_SERIES_DAYS is trimmed to the
     * most recent slice. A caller that shows period totals beside this chart
     * has to be able to say so, otherwise the two describe different periods
     * and nothing on screen admits it.
     *
     * @return array{
     *     series: array<int, array{date: string, volume: int, fees: int}>,
     *     window: array{from: string, to: string, days: int, truncated: bool}
     * }
     */
    private function dailySeriesWithWindow(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $start = CarbonImmutable::instance(Carbon::instance($from))->startOfDay();
        $end = CarbonImmutable::instance(Carbon::instance($to))->startOfDay();

        // The request layer rejects a reversed range, but this service is also
        // called directly. Carbon 3's diffInDays is signed, so without this the
        // day count goes negative and the series runs backwards through time.
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        // Bound the response: an "all time" range would otherwise return a
        // point per day since the first payment.
        $requested = (int) $start->diffInDays($end) + 1;
        $days = min($requested, self::MAX_SERIES_DAYS);
        $start = $end->subDays($days - 1);

        // Half-open range on the raw column rather than whereDate(), which
        // wraps created_at in a function and gives up the index on it.
        $byDay = Transaction::query()
            ->withStatus(TransactionStatus::Successful->value)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end->addDay())
            ->selectRaw('DATE(created_at) as day, SUM(gross_amount) as volume, SUM(fee_amount) as fees')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($start, $byDay) {
                $date = $start->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'volume' => (int) ($byDay[$date]->volume ?? 0),
                    'fees' => (int) ($byDay[$date]->fees ?? 0),
                ];
            })
            ->all();

        return [
            'series' => $series,
            'window' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'days' => $days,
                'truncated' => $requested > $days,
            ],
        ];
    }

    /**
     * Who is holding the money waiting to be settled, largest first. The
     * dashboard draws this as proportional bands, so the composition of the
     * float is visible and not just its total.
     *
     * Only the largest `$limit` holders are named, but the count and the value
     * of everyone else are returned alongside them. The band divides by the
     * whole float, so without the remainder its strips would stop summing to
     * 100% the moment there were more holders than the cap — and "held across
     * N merchants" would silently report the cap instead of the truth.
     *
     * @return array{
     *     holders: array<int, array{id: int, business_name: string, wallet_balance: int}>,
     *     holder_count: int,
     *     remainder_count: int,
     *     remainder_amount: int
     * }
     */
    public function unsettledFloat(int $limit = 40): array
    {
        $positive = Wallet::query()->where('balance', '>', 0);

        $holderCount = (int) $positive->clone()->count();
        $floatTotal = (int) $positive->clone()->sum('balance');

        $holders = Merchant::query()
            ->join('wallets', 'wallets.merchant_id', '=', 'merchants.id')
            ->where('wallets.balance', '>', 0)
            ->orderByDesc('wallets.balance')
            ->orderBy('merchants.id')
            ->limit($limit)
            ->get(['merchants.id', 'merchants.business_name', 'wallets.balance'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'business_name' => $row->business_name,
                'wallet_balance' => (int) $row->balance,
            ])
            ->all();

        $named = array_sum(array_column($holders, 'wallet_balance'));

        return [
            'holders' => $holders,
            'holder_count' => $holderCount,
            'remainder_count' => max(0, $holderCount - count($holders)),
            'remainder_amount' => max(0, $floatTotal - $named),
        ];
    }

    /**
     * Settlement history as runs rather than as rows.
     *
     * A run is the unit of the operator's work: one sweep, one batch reference,
     * one total. Listing the individual records flat means repeating the same
     * batch reference on every line and never stating what the run moved — the
     * figure the person actually came to check.
     *
     * Aggregated in SQL rather than grouped client-side, because a batch can
     * straddle a page boundary and a per-page grouping would quietly show a
     * partial total as if it were the whole run.
     */
    public function settlementBatches(
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
        int $perPage,
    ): LengthAwarePaginator {
        return Settlement::query()
            ->settledBetween($from, $to)
            ->selectRaw('batch_ref, MIN(settled_at) as settled_at, SUM(amount) as total_amount, COUNT(*) as merchant_count')
            ->groupBy('batch_ref')
            ->orderByDesc('settled_at')
            ->paginate($perPage)
            ->through(fn ($row) => [
                'batch_ref' => (string) $row->batch_ref,
                'settled_at' => (string) $row->settled_at,
                'total_amount' => (int) $row->total_amount,
                'merchant_count' => (int) $row->merchant_count,
            ]);
    }

    /**
     * The most recent settlement run, summarised across its whole batch.
     *
     * The dashboard's other figures answer "how much is waiting"; this answers
     * "when did I last sweep", which is what decides whether a run is due.
     *
     * @return array{batch_ref: string, settled_at: string, amount: int, merchant_count: int}|null
     */
    public function lastSettlement(): ?array
    {
        $batch = Settlement::query()
            ->selectRaw('batch_ref, MAX(settled_at) as settled_at, SUM(amount) as amount, COUNT(*) as merchant_count')
            ->groupBy('batch_ref')
            // Order by the batch's own timestamp, not insertion order: a run
            // backdated during seeding would otherwise win on id.
            ->orderByDesc('settled_at')
            ->first();

        if ($batch === null) {
            return null;
        }

        return [
            'batch_ref' => $batch->batch_ref,
            'settled_at' => (string) $batch->settled_at,
            'amount' => (int) $batch->amount,
            'merchant_count' => (int) $batch->merchant_count,
        ];
    }

    /** Period totals for the reports screen. */
    public function summary(?DateTimeInterface $from, ?DateTimeInterface $to): array
    {
        $transactions = Transaction::query()->createdBetween($from, $to);
        $settlements = Settlement::query()->settledBetween($from, $to);

        $countOf = fn (TransactionStatus $status) => $transactions->clone()
            ->withStatus($status->value)
            ->count();

        $successful = $transactions->clone()->withStatus(TransactionStatus::Successful->value);

        // With no explicit range the totals cover everything, so the chart has
        // to start at the first payment rather than at an arbitrary 30 days —
        // otherwise the figures above it and the line below it describe
        // different periods. dailySeriesWithWindow still caps the span and
        // reports when it had to, which is what the UI labels.
        $charted = $this->dailySeriesWithWindow(
            $from ?? $this->firstActivityDate(),
            $to ?? now()->endOfDay(),
        );

        return [
            'platform_fees' => (int) $successful->clone()->sum('fee_amount'),
            'successful_volume' => (int) $successful->clone()->sum('gross_amount'),
            'total_settled' => (int) $settlements->sum('amount'),
            'transaction_counts' => [
                'successful' => $countOf(TransactionStatus::Successful),
                'pending' => $countOf(TransactionStatus::Pending),
                'failed' => $countOf(TransactionStatus::Failed),
            ],
            'series' => $charted['series'],
            'series_window' => $charted['window'],
        ];
    }

    /**
     * The day the ledger starts, for an unbounded "all time" range. Falls back
     * to a 30-day window on an empty ledger, which is only ever the zero state.
     */
    private function firstActivityDate(): CarbonImmutable
    {
        $earliest = Transaction::query()->min('created_at');

        return $earliest !== null
            ? CarbonImmutable::parse($earliest)->startOfDay()
            : CarbonImmutable::instance(now())->subDays(29)->startOfDay();
    }
}
