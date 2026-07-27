<?php

namespace Database\Factories;

use App\Enums\InstitutionType;
use App\Enums\MerchantStatus;
use App\Models\Institution;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchant>
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
            // Generated to match the institution, so seeded merchants satisfy
            // the same rule the registration form enforces.
            'account_number' => fn (array $attributes) => self::accountNumberFor(
                $attributes['institution_id']
            ),
            'status' => MerchantStatus::Active,
        ];
    }

    /** A mobile money wallet is a mobile number; a bank account is not. */
    public static function accountNumberFor(mixed $institutionId): string
    {
        $institution = $institutionId instanceof Institution
            ? $institutionId
            : Institution::find($institutionId);

        return $institution?->type === InstitutionType::MobileMoney
            ? '0'.fake()->randomElement(['24', '20', '55', '27', '54', '50']).fake()->numerify('#######')
            : fake()->numerify('##########');
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => MerchantStatus::Inactive]);
    }
}
