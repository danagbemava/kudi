<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'business_name' => $this->business_name,
            'account_number' => $this->account_number,
            'institution' => new InstitutionResource($this->whenLoaded('institution')),
            'status' => $this->status,
            'wallet_balance' => $this->whenLoaded('wallet', fn () => $this->wallet->balance),
            'created_at' => $this->created_at,
        ];
    }
}
