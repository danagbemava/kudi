<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMerchantRequest;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\SettlementResource;
use App\Http\Resources\TransactionResource;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MerchantController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $merchants = Merchant::query()
            ->with(['institution', 'wallet'])
            ->search($request->string('search'))
            ->withStatus($request->string('status'))
            ->latest()
            ->paginate($this->perPage($request));

        return MerchantResource::collection($merchants);
    }

    public function store(StoreMerchantRequest $request): JsonResponse
    {
        $merchant = Merchant::create($request->validated());

        return MerchantResource::make($merchant->load(['institution', 'wallet']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Merchant $merchant): JsonResponse
    {
        $merchant->load(['institution', 'wallet']);

        $profile = MerchantResource::make($merchant)->resolve();

        $profile['recent_transactions'] = TransactionResource::collection(
            $merchant->transactions()->latest()->limit(10)->get()
        )->resolve();

        $profile['recent_settlements'] = SettlementResource::collection(
            $merchant->settlements()->latest('settled_at')->limit(10)->get()
        )->resolve();

        $profile['stats'] = [
            'total_settled' => (int) $merchant->settlements()->sum('amount'),
            'transaction_count' => $merchant->transactions()->count(),
            'successful_volume' => (int) $merchant->transactions()
                ->where('status', TransactionStatus::Successful)
                ->sum('gross_amount'),
        ];

        return response()->json(['data' => $profile]);
    }

    public function updateStatus(Request $request, Merchant $merchant): MerchantResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $merchant->update(['status' => $validated['status']]);

        return MerchantResource::make($merchant->load(['institution', 'wallet']));
    }
}
