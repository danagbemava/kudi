<?php

namespace Database\Factories;

use App\Enums\InstitutionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Institution>
 */
class InstitutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Bank',
            'code' => fake()->unique()->lexify('???'),
            'type' => InstitutionType::Bank,
        ];
    }

    public function mobileMoney(): static
    {
        return $this->state(fn () => ['type' => InstitutionType::MobileMoney]);
    }
}
