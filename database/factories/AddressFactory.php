<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Work', 'Other']),
            'recipient_name' => fake()->name(),
            'phone' => '9'.fake()->numerify('#########'),
            'line1' => fake()->buildingNumber().', '.fake()->streetName(),
            'line2' => fake()->optional()->streetName(),
            'landmark' => fake()->optional()->words(2, true),
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => fake()->randomElement(['800001', '800004', '800013', '800020']),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => ['is_default' => true]);
    }
}
