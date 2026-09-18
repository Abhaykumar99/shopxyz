<?php

namespace Database\Factories;

use App\Enums\CashSettlementStatus;
use App\Models\CodSettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodSettlement>
 */
class CodSettlementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'CS-'.fake()->unique()->numberBetween(2000, 9999),
            'user_id' => User::factory(),
            'amount_paise' => fake()->numberBetween(10000, 300000),
            'status' => CashSettlementStatus::AwaitingVerification,
            'handed_over_at' => now(),
        ];
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CashSettlementStatus::Settled,
            'verified_at' => now(),
            'counted_paise' => $attributes['amount_paise'],
        ]);
    }
}
