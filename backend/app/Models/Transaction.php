<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeForMerchant(Builder $query, ?int $merchantId): Builder
    {
        return $merchantId ? $query->where('merchant_id', $merchantId) : $query;
    }

    public function scopeReferenceLike(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $term === ''
            ? $query
            : $query->whereRaw('LOWER(reference) LIKE ?', ['%'.mb_strtolower($term).'%']);
    }

    public function scopeCreatedBetween(
        Builder $query,
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
    ): Builder {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }
}
