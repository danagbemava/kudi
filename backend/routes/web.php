<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['service' => 'Kudi Merchant Dashboard API']));

/*
 * Auth lives under /api so it does not collide with the SPA's own /login
 * *page* — the dev proxy and nginx both forward /api to Laravel, and a
 * backend route at /login would swallow a direct visit or refresh.
 *
 * Registered here rather than in routes/api.php so it always gets the web
 * middleware group: session and CSRF are unconditional, whereas Sanctum only
 * applies them to /api routes it recognises as coming from a stateful origin.
 */
Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
});
