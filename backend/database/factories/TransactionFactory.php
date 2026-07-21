<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Merchant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $gross = fake()->numberBetween(500, 500_000); // GH₵5 – GH₵5,000 in pesewas
        $fee = Money::fee($gross);

        return [
            'merchant_id' => Merchant::factory(),
            'reference' => 'PAY-'.strtoupper(Str::random(8)),
            'gross_amount' => $gross,
            'fee_amount' => $fee,
            'net_amount' => $gross - $fee,
            'status' => TransactionStatus::Successful,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => TransactionStatus::Pending]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => TransactionStatus::Failed]);
    }
}
