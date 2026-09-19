<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_name' => fake()->words(3, true),
            'variant_name' => 'Standard',
            'sku' => strtoupper(fake()->bothify('??-###-##')),
            'mrp_paise' => $mrp = fake()->numberBetween(10000, 100000),
            'unit_price_paise' => $price = (int) round($mrp * 0.9),
            'quantity' => $quantity = fake()->numberBetween(1, 4),
            'line_total_paise' => $price * $quantity,
            'is_wholesale' => false,
        ];
    }
}
