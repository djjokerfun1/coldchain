<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Shipping;

use App\Domain\Ordering\Models\Order;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'driver_id' => null,
            'vehicle_id' => null,
            'reference' => strtoupper('SHP-'.$this->faker->unique()->bothify('######')),
            'status' => ShipmentStatus::Pending,
        ];
    }
}
