<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Ordering;

use App\Domain\Ordering\Enums\OrderStatus;
use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\ValueObjects\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'reference' => strtoupper('ORD-'.$this->faker->unique()->bothify('######')),
            'status' => OrderStatus::Draft,
            'pickup_address' => $this->fakeAddress(),
            'delivery_address' => $this->fakeAddress(),
            'placed_at' => null,
        ];
    }

    public function placed(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Placed,
            'placed_at' => now(),
        ]);
    }

    private function fakeAddress(): Address
    {
        return new Address(
            line1: $this->faker->streetAddress(),
            line2: null,
            city: $this->faker->city(),
            postalCode: $this->faker->postcode(),
            country: $this->faker->countryCode(),
        );
    }
}
