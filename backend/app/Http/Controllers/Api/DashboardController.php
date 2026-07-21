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
        return response()->json(['data' => [
            'totals' => $reporting->totals(),
            'volume_series' => $reporting->volumeSeries(),
            'unsettled' => $reporting->unsettledFloat(),
            'last_settlement' => $reporting->lastSettlement(),
            'recent_transactions' => TransactionResource::collection(
                Transaction::with('merchant')->latest()->limit(8)->get()
            )->resolve(),
        ]]);
    }
}
