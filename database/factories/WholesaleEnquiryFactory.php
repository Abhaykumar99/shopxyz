<?php

namespace Database\Factories;

use App\Enums\WholesaleEnquiryStatus;
use App\Models\WholesaleEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WholesaleEnquiry>
 */
class WholesaleEnquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'WQ-'.fake()->unique()->numberBetween(5000, 9999),
            'business_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'phone' => '9'.fake()->numerify('#########'),
            'email' => fake()->optional()->safeEmail(),
            'business_type' => fake()->randomElement(['retail', 'events', 'corporate', 'hospitality', 'other']),
            'city' => 'Patna',
            'pincode' => '800001',
            'message' => fake()->sentence(12),
            'status' => WholesaleEnquiryStatus::New,
        ];
    }
}
