<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $transactions = Transaction::query()
            ->with('merchant.institution')
            ->withStatus($request->string('status'))
            ->forMerchant($request->integer('merchant_id') ?: null)
            ->referenceLike($request->string('search'))
            ->createdBetween($request->date('from'), $request->date('to'))
            ->latest()
            ->paginate($this->perPage($request));

        return TransactionResource::collection($transactions);
    }

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
