<?php

namespace Database\Factories;

use App\Enums\MerchantStatus;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Merchant>
 */
class MerchantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->company(),
            'institution_id' => Institution::factory(),
            'account_number' => fake()->numerify('##########'),
            'status' => MerchantStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => MerchantStatus::Inactive]);
    }
}
