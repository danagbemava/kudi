<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettlementResource;
use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SettlementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $settlements = Settlement::query()
            ->with('merchant.institution')
            ->when($request->filled('from'), fn ($q) => $q
                ->whereDate('settled_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q
                ->whereDate('settled_at', '<=', $request->date('to')))
            ->latest('settled_at')
            ->latest('id')
            ->paginate(min($request->integer('per_page', 15), 100));

        return SettlementResource::collection($settlements);
    }

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
