<?php

namespace Database\Factories;

use App\Enums\DeliveryStep;
use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAssignment>
 */
class DeliveryAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'step' => DeliveryStep::Assigned,
            'is_active' => true,
            'assigned_at' => now(),
            'otp' => fake()->numerify('######'),
        ];
    }

    public function step(DeliveryStep $step): static
    {
        return $this->state(fn (array $attributes): array => ['step' => $step]);
    }
}
