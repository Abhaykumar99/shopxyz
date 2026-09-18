<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'invoice_number' => 'INV/2026-27/'.fake()->unique()->numerify('#####'),
            'issued_at' => now(),
            'subtotal_paise' => $subtotal = fake()->numberBetween(20000, 500000),
            'discount_paise' => 0,
            'delivery_charge_paise' => 0,
            'total_paise' => $subtotal,
        ];
    }
}
