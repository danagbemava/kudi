<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(ReportingService $reporting): JsonResponse
    {
        $float = $reporting->unsettledFloat();

        return response()->json(['data' => [
            'totals' => $reporting->totals(),
            'volume_series' => $reporting->volumeSeries(),
            'unsettled' => $float['holders'],
            'unsettled_meta' => [
                'holder_count' => $float['holder_count'],
                'remainder_count' => $float['remainder_count'],
                'remainder_amount' => $float['remainder_amount'],
            ],
            'last_settlement' => $reporting->lastSettlement(),
            'recent_transactions' => TransactionResource::collection(
                Transaction::with('merchant')->latest()->limit(8)->get()
            )->resolve(),
        ]]);
    }
}
