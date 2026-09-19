<?php

namespace Database\Factories;

use App\Models\PriceSlab;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceSlab>
 */
class PriceSlabFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'min_quantity' => 10,
            'unit_price_paise' => fake()->numberBetween(10000, 100000),
            'is_active' => true,
        ];
    }
}
