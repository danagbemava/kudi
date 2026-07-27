<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkMerchantStatusRequest;
use App\Http\Requests\MerchantIndexRequest;
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
    public function index(MerchantIndexRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $this->sort($request, MerchantIndexRequest::SORTABLE, 'merchants.created_at');

        $merchants = Merchant::query()
            ->with(['institution', 'wallet'])
            // Wallet balance lives on another table, so ordering by it needs
            // the join. Left, not inner: a merchant whose wallet row is somehow
            // missing should still be listed rather than vanish from the table.
            ->leftJoin('wallets', 'wallets.merchant_id', '=', 'merchants.id')
            ->select('merchants.*')
            ->search($request->string('search'))
            ->withStatus($request->string('status'))
            ->orderBy($column, $direction)
            ->orderBy('merchants.id', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

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

    /**
     * Activate or deactivate several merchants at once. Reports how many rows
     * actually changed, not how many were named — re-applying a status a
     * merchant already has is not a change, and saying otherwise would make
     * the confirmation message untrue.
     */
    public function bulkStatus(BulkMerchantStatusRequest $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $updated = Merchant::query()
            ->whereIn('id', $request->input('ids'))
            ->where('status', '!=', $status)
            ->update(['status' => $status]);

        return response()->json(['data' => ['updated' => $updated, 'status' => $status]]);
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
