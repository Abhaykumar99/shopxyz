<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.fake()->unique()->numberBetween(10000, 99999),
            'user_id' => User::factory(),
            'status' => OrderStatus::Placed,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::CodPending,
            'ship_name' => fake()->name(),
            'ship_phone' => '9'.fake()->numerify('#########'),
            'ship_line1' => fake()->buildingNumber().', '.fake()->streetName(),
            'ship_city' => 'Patna',
            'ship_state' => 'Bihar',
            'ship_pincode' => '800001',
            'subtotal_paise' => $subtotal = fake()->numberBetween(20000, 500000),
            'discount_paise' => 0,
            'delivery_charge_paise' => $delivery = fake()->randomElement([0, 4000]),
            'total_paise' => $subtotal + $delivery,
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }

    public function upi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::Upi,
            'payment_status' => PaymentStatus::AwaitingProof,
        ]);
    }
}
