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
            ->when($request->filled('status'), fn ($q) => $q
                ->where('status', $request->string('status')->toString()))
            ->when($request->filled('merchant_id'), fn ($q) => $q
                ->where('merchant_id', $request->integer('merchant_id')))
            ->when($request->filled('search'), fn ($q) => $q
                ->whereRaw('LOWER(reference) LIKE ?', ['%'.$request->string('search')->trim()->lower().'%']))
            ->when($request->filled('from'), fn ($q) => $q
                ->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q
                ->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 100));

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
