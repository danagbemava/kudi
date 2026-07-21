<?php

namespace App\Models;

use App\Enums\InstitutionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'type'];

    protected function casts(): array
    {
        return [
            'type' => InstitutionType::class,
        ];
    }

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }
}
