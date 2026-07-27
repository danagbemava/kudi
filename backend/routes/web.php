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
    // No `guest` middleware: it answers an already-signed-in caller with a 302
    // to an HTML page, which is not a shape this API speaks. AuthController
    // handles that case in JSON, and rate-limits failed attempts itself so a
    // success can clear the tally.
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
});
