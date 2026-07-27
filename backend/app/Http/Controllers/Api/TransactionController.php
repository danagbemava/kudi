<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $this->sort($request, TransactionIndexRequest::SORTABLE, 'created_at');

        $transactions = Transaction::query()
            ->with('merchant.institution')
            ->withStatus($request->string('status'))
            ->forMerchant($request->integer('merchant_id') ?: null)
            ->referenceLike($request->string('search'))
            ->createdBetween($request->date('from'), $request->date('to'))
            ->orderBy($column, $direction)
            // Ledger rows share a timestamp often enough that paging without a
            // tiebreaker can show the same row twice across pages.
            ->orderBy('id', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return TransactionResource::collection($transactions);
    }

    /**
     * A single transaction, resolved by reference so a link to one keeps
     * working wherever it falls in the ledger.
     */
    public function show(Transaction $transaction): TransactionResource
    {
        return TransactionResource::make($transaction->load('merchant.institution'));
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
