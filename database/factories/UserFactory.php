<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Ordering\Models\Client;
use App\Domain\Shipping\Models\Driver;
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
            'remember_token' => Str::random(10),
            'role' => UserRole::Planner,
        ];
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

    public function planner(): static
    {
        return $this->state(['role' => UserRole::Planner, 'driver_id' => null, 'client_id' => null]);
    }

    public function driver(?Driver $driver = null): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Driver,
            'driver_id' => ($driver ?? Driver::factory()->create())->id,
            'client_id' => null,
        ]);
    }

    public function client(?Client $client = null): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Client,
            'driver_id' => null,
            'client_id' => ($client ?? Client::factory()->create())->id,
        ]);
    }
}
