<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request, ReportingService $reporting): JsonResponse
    {
        return response()->json([
            'data' => $reporting->summary($request->date('from'), $request->date('to')),
        ]);
    }
}
