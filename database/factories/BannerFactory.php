<?php

namespace Database\Factories;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement' => BannerPlacement::DesktopHero,
            'eyebrow' => fake()->randomElement(['Fresh today', 'Festive', 'New in']),
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(8),
            'cta_label' => 'Start shopping',
            'cta_url' => '/categories',
            'theme' => 'brand',
            'sort_order' => 0,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function placement(BannerPlacement $placement): static
    {
        return $this->state(fn (array $attributes): array => ['placement' => $placement]);
    }

    /**
     * Saved, but not on the site yet.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => ['starts_at' => now()->addDay()]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
