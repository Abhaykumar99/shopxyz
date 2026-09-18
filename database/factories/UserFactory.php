<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '9'.fake()->numerify('#########'),
            'role' => UserRole::Customer,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * A customer who signed in with Google: no password, a google_id instead (ADR-004).
     */
    public function googleCustomer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Customer,
            'password' => null,
            'google_id' => (string) fake()->unique()->numerify('##################'),
            'avatar_url' => 'https://lh3.googleusercontent.com/'.fake()->lexify('????????'),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => UserRole::Admin]);
    }

    public function deliveryPartner(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => UserRole::Delivery]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
