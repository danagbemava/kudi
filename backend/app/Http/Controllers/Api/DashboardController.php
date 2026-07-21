<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $successful = Transaction::query()->where('status', TransactionStatus::Successful);

        $totals = [
            'wallet_balance' => (int) Wallet::sum('balance'),
            'payment_volume' => (int) $successful->clone()->sum('gross_amount'),
            'platform_fees' => (int) $successful->clone()->sum('fee_amount'),
            'settled' => (int) Settlement::sum('amount'),
            'merchant_count' => Merchant::count(),
            'pending_count' => Transaction::where('status', TransactionStatus::Pending)->count(),
        ];

        return response()->json(['data' => [
            'totals' => $totals,
            'volume_series' => $this->volumeSeries(),
            'recent_transactions' => TransactionResource::collection(
                Transaction::with('merchant')->latest()->limit(8)->get()
            )->resolve(),
            'unsettled' => $this->unsettledFloat(),
        ]]);
    }

    /**
     * Who is actually holding the money waiting to be settled, largest first.
     * The dashboard renders this as proportional bands, so the composition of
     * the float is visible rather than just its total.
     *
     * @return array<int, array{id: int, business_name: string, wallet_balance: int}>
     */
    private function unsettledFloat(): array
    {
        return Merchant::query()
            ->join('wallets', 'wallets.merchant_id', '=', 'merchants.id')
            ->where('wallets.balance', '>', 0)
            ->orderByDesc('wallets.balance')
            ->limit(40)
            ->get(['merchants.id', 'merchants.business_name', 'wallets.balance'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'business_name' => $row->business_name,
                'wallet_balance' => (int) $row->balance,
            ])
            ->all();
    }

    /**
     * Daily successful payment volume for the last 30 days, zero-filled.
     *
     * @return array<int, array{date: string, volume: int, fees: int}>
     */
    private function volumeSeries(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $byDay = Transaction::query()
            ->where('status', TransactionStatus::Successful)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(gross_amount) as volume, SUM(fee_amount) as fees')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return collect(range(0, 29))->map(function (int $offset) use ($start, $byDay) {
            $date = $start->clone()->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'volume' => (int) ($byDay[$date]->volume ?? 0),
                'fees' => (int) ($byDay[$date]->fees ?? 0),
            ];
        })->all();
    }
}
