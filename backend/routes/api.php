<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/transactions/{transaction}/process', [TransactionController::class, 'process']);
});
