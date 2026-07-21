<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'reference',
        'gross_amount',
        'fee_amount',
        'net_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'fee_amount' => 'integer',
            'net_amount' => 'integer',
            'status' => TransactionStatus::class,
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }
}
