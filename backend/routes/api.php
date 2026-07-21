<?php

use App\Http\Controllers\Api\InstitutionController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/institutions', [InstitutionController::class, 'index']);

    Route::get('/merchants', [MerchantController::class, 'index']);
    Route::post('/merchants', [MerchantController::class, 'store']);
    Route::get('/merchants/{merchant}', [MerchantController::class, 'show']);
    Route::patch('/merchants/{merchant}/status', [MerchantController::class, 'updateStatus']);

    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/transactions/{transaction}/process', [TransactionController::class, 'process']);
    Route::post('/settlements/run', [SettlementController::class, 'run']);
});
