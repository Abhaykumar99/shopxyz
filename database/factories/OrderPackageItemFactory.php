<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderPackage;
use App\Models\OrderPackageItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPackageItem>
 */
class OrderPackageItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_package_id' => OrderPackage::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => 1,
        ];
    }
}
