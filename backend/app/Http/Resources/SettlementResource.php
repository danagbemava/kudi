<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'batch_ref' => $this->batch_ref,
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
            'amount' => $this->amount,
            'settled_at' => $this->settled_at,
        ];
    }
}
