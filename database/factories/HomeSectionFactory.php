<?php

namespace Database\Factories;

use App\Enums\HomeSectionType;
use App\Models\HomeSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeSection>
 */
class HomeSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'type' => HomeSectionType::ProductRail,
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(6),
            'settings' => ['source' => 'featured', 'limit' => 8, 'rail' => true],
            'sort_order' => 0,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function type(HomeSectionType $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type]);
    }

    /**
     * A rail whose products the admin picked by hand.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => HomeSectionType::ProductRail,
            'settings' => ['source' => 'manual', 'limit' => 8, 'rail' => true],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
