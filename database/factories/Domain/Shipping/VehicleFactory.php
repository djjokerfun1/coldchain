<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Shipping;

use App\Domain\Shipping\Models\Driver;
use App\Domain\Shipping\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'registration' => strtoupper($this->faker->unique()->bothify('??-###-??')),
            'driver_id' => Driver::factory(),
        ];
    }
}
