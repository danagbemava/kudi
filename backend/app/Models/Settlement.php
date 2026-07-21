<?php

namespace App\Models;

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

    public function scopeSettledBetween(
        Builder $query,
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
    ): Builder {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('settled_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('settled_at', '<=', $to));
    }
}
