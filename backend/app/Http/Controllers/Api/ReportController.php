<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $transactions = Transaction::query()
            ->when($request->filled('from'), fn ($q) => $q
                ->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q
                ->whereDate('created_at', '<=', $request->date('to')));

        $settlements = Settlement::query()
            ->when($request->filled('from'), fn ($q) => $q
                ->whereDate('settled_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q
                ->whereDate('settled_at', '<=', $request->date('to')));

        $successful = $transactions->clone()->where('status', TransactionStatus::Successful);

        return response()->json(['data' => [
            'platform_fees' => (int) $successful->clone()->sum('fee_amount'),
            'successful_volume' => (int) $successful->clone()->sum('gross_amount'),
            'total_settled' => (int) $settlements->sum('amount'),
            'transaction_counts' => [
                'successful' => $transactions->clone()->where('status', TransactionStatus::Successful)->count(),
                'pending' => $transactions->clone()->where('status', TransactionStatus::Pending)->count(),
                'failed' => $transactions->clone()->where('status', TransactionStatus::Failed)->count(),
            ],
        ]]);
    }
}
