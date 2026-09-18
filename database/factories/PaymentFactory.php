<?php

namespace Database\Factories;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => PaymentMethod::Upi,
            'amount_paise' => fake()->numberBetween(20000, 500000),
            'status' => PaymentAttemptStatus::Submitted,
            'utr' => fake()->unique()->numerify('############'),
            'submitted_at' => now(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentAttemptStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'We could not find this UTR in our account.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentAttemptStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }
}
