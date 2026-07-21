<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'reference' => 'STL-'.strtoupper(Str::random(8)),
            'amount' => fake()->numberBetween(10_000, 2_000_000),
            'batch_ref' => 'BATCH-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'settled_at' => now(),
        ];
    }
}
