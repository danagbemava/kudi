<?php

namespace App\Http\Resources;

use App\Enums\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
            'gross_amount' => $this->gross_amount,
            'fee_amount' => $this->fee_amount,
            'net_amount' => $this->net_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            // When the gateway resolved it. A transaction is only ever written
            // twice — created pending, then settled into a final status — so
            // updated_at is that moment. Null while still pending, rather than
            // reporting the creation time as though it had been decided.
            'processed_at' => $this->status === TransactionStatus::Pending ? null : $this->updated_at,
        ];
    }
}
