<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('??-###-##')),
            'name' => 'Standard',
            'mrp_paise' => $mrp = fake()->numberBetween(10000, 200000),
            'price_paise' => (int) round($mrp * fake()->randomFloat(2, 0.7, 1)),
            'stock_quantity' => fake()->numberBetween(0, 80),
            'low_stock_threshold' => 5,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => ['stock_quantity' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes): array => ['stock_quantity' => 2]);
    }
}
