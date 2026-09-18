<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPackage>
 */
class OrderPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'package_id' => 'PKG-'.fake()->unique()->numerify('#####-#'),
            'sequence' => 1,
            'pickup_code' => fake()->numerify('######'),
        ];
    }
}
