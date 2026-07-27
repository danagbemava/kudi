<?php

use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InstitutionController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/config', ConfigController::class);
    Route::get('/dashboard', DashboardController::class);
    Route::get('/institutions', [InstitutionController::class, 'index']);

    Route::get('/merchants', [MerchantController::class, 'index']);
    Route::post('/merchants', [MerchantController::class, 'store']);
    // Declared before the {merchant} route so "status" is not read as an id.
    Route::patch('/merchants/status', [MerchantController::class, 'bulkStatus']);
    Route::get('/merchants/{merchant}', [MerchantController::class, 'show']);
    Route::patch('/merchants/{merchant}/status', [MerchantController::class, 'updateStatus']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    // Bound on the reference, because that is what a shared link carries.
    Route::get('/transactions/{transaction:reference}', [TransactionController::class, 'show']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/transactions/{transaction}/process', [TransactionController::class, 'process']);

    Route::get('/settlements', [SettlementController::class, 'index']);
    // History as runs rather than rows — declared before nothing in
    // particular, but kept next to the listing it summarises.
    Route::get('/settlements/batches', [SettlementController::class, 'batches']);
    Route::post('/settlements/run', [SettlementController::class, 'run']);

    Route::get('/reports/summary', [ReportController::class, 'summary']);
});
