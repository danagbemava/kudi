<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;

class SettlementController extends Controller
{
    public function run(SettlementService $settlements): JsonResponse
    {
        $result = $settlements->run();

        if ($result === null) {
            return response()->json([
                'message' => 'There are no positive wallet balances to settle.',
            ], 422);
        }

        return response()->json(['data' => $result]);
    }
}
