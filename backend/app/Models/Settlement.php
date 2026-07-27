<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'reference',
        'amount',
        'batch_ref',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'settled_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /** Narrow to one settlement run. No-ops on null, like the other filters. */
    public function scopeInBatch(Builder $query, ?string $batchRef): Builder
    {
        $batchRef = trim((string) $batchRef);

        return $batchRef === '' ? $query : $query->where('batch_ref', $batchRef);
    }

    /**
     * Both ends inclusive by day, as a half-open range on the raw column so the
     * index on settled_at is still usable. See Transaction::scopeCreatedBetween.
     */
    public function scopeSettledBetween(
        Builder $query,
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
    ): Builder {
        return $query
            ->when($from, fn (Builder $q) => $q->where(
                'settled_at', '>=', CarbonImmutable::instance($from)->startOfDay()
            ))
            ->when($to, fn (Builder $q) => $q->where(
                'settled_at', '<', CarbonImmutable::instance($to)->startOfDay()->addDay()
            ));
    }
}
