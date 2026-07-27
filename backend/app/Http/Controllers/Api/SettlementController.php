<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettlementIndexRequest;
use App\Http\Resources\SettlementResource;
use App\Models\Settlement;
use App\Services\ReportingService;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SettlementController extends Controller
{
    public function index(SettlementIndexRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $this->sort($request, SettlementIndexRequest::SORTABLE, 'settled_at');

        $settlements = Settlement::query()
            ->with('merchant.institution')
            ->settledBetween($request->date('from'), $request->date('to'))
            ->inBatch($request->string('batch'))
            ->orderBy($column, $direction)
            // Every row in a batch shares one settled_at, so id keeps paging
            // stable within a run.
            ->orderBy('id', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return SettlementResource::collection($settlements);
    }

    /**
     * Settlement history grouped into the runs that produced it.
     *
     * The flat index above still exists for drilling into one batch; this is
     * what the history screen leads with.
     */
    public function batches(SettlementIndexRequest $request, ReportingService $reporting): JsonResponse
    {
        $batches = $reporting->settlementBatches(
            $request->date('from'),
            $request->date('to'),
            $this->perPage($request),
        );

        return response()->json($batches->toArray());
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
