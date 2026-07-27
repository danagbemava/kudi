<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The handful of server-owned constants the interface has to know.
 *
 * The fee rate is here because the payment simulator previews the split live as
 * you type, which means the rate exists on both sides. Hardcoding it in the
 * frontend made that a copy: changing KUDI_FEE_BASIS_POINTS moved what the
 * backend charged while the preview went on quoting 1.5%. Now there is one
 * source and the client reads it.
 */
class ConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => [
            'fee_basis_points' => (int) config('kudi.fee_basis_points', 150),
            'currency' => (string) config('kudi.currency', 'GHS'),
        ]]);
    }
}
