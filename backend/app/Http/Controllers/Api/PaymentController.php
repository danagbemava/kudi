<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Merchant;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, PaymentService $payments): JsonResponse
    {
        $merchant = Merchant::findOrFail($request->integer('merchant_id'));

        $transaction = $payments->create(
            $merchant,
            $request->integer('amount'),
            $request->string('reference')->toString(),
        );

        return TransactionResource::make($transaction->load('merchant'))
            ->response()
            ->setStatusCode(201);
    }
}
