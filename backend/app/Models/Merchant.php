<?php

namespace App\Models;

use App\Enums\MerchantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Merchant extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'active',
    ];

    protected $fillable = [
        'name',
        'email',
        'business_name',
        'institution_id',
        'account_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => MerchantStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // Every merchant owns exactly one wallet (single-currency assumption,
        // documented in the README). Creating it here keeps the invariant true
        // for controllers, factories, and seeders alike.
        static::created(function (Merchant $merchant) {
            $merchant->wallet()->create();
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function isActive(): bool
    {
        return $this->status === MerchantStatus::Active;
    }
}
