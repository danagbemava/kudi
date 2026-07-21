<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use DateTimeInterface;
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
            // What is still in flight. Pending money is float that has not
            // landed in a wallet yet, so it changes what settling now catches.
            'pending_amount' => (int) $pending->clone()->sum('gross_amount'),
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
        $start = CarbonImmutable::instance(Carbon::instance($from))->startOfDay();
        $end = CarbonImmutable::instance(Carbon::instance($to))->startOfDay();

        // Bound the response: an "all time" range would otherwise return a
        // point per day since the first payment.
        $days = min($start->diffInDays($end) + 1, self::MAX_SERIES_DAYS);
        $start = $end->subDays($days - 1);

        $byDay = Transaction::query()
            ->withStatus(TransactionStatus::Successful->value)
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw('DATE(created_at) as day, SUM(gross_amount) as volume, SUM(fee_amount) as fees')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($start, $byDay) {
                $date = $start->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'volume' => (int) ($byDay[$date]->volume ?? 0),
                    'fees' => (int) ($byDay[$date]->fees ?? 0),
                ];
            })
            ->all();
    }

    /**
     * Who is holding the money waiting to be settled, largest first. The
     * dashboard draws this as proportional bands, so the composition of the
     * float is visible and not just its total.
     *
     * @return array<int, array{id: int, business_name: string, wallet_balance: int}>
     */
    public function unsettledFloat(int $limit = 40): array
    {
        return Merchant::query()
            ->join('wallets', 'wallets.merchant_id', '=', 'merchants.id')
            ->where('wallets.balance', '>', 0)
            ->orderByDesc('wallets.balance')
            ->limit($limit)
            ->get(['merchants.id', 'merchants.business_name', 'wallets.balance'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'business_name' => $row->business_name,
                'wallet_balance' => (int) $row->balance,
            ])
            ->all();
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

        return [
            'platform_fees' => (int) $successful->clone()->sum('fee_amount'),
            'successful_volume' => (int) $successful->clone()->sum('gross_amount'),
            'total_settled' => (int) $settlements->sum('amount'),
            'transaction_counts' => [
                'successful' => $countOf(TransactionStatus::Successful),
                'pending' => $countOf(TransactionStatus::Pending),
                'failed' => $countOf(TransactionStatus::Failed),
            ],
            'series' => $this->dailySeries(
                $from ?? now()->subDays(29)->startOfDay(),
                $to ?? now()->endOfDay(),
            ),
        ];
    }
}
