<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DateRangeRequest;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function summary(DateRangeRequest $request, ReportingService $reporting): JsonResponse
    {
        return response()->json([
            'data' => $reporting->summary($request->date('from'), $request->date('to')),
        ]);
    }
}
