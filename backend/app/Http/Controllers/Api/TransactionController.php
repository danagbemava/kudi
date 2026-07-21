<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function process(Transaction $transaction, PaymentService $payments): TransactionResource|JsonResponse
    {
        if (! $transaction->isPending()) {
            return response()->json([
                'message' => 'Only pending transactions can be processed.',
            ], 422);
        }

        return TransactionResource::make(
            $payments->process($transaction)->load('merchant')
        );
    }
}
