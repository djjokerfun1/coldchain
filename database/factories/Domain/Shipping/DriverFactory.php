<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Shipping;

use App\Domain\Shipping\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'license_number' => strtoupper($this->faker->unique()->bothify('DL-#######')),
        ];
    }
}
