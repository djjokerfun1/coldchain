<?php

declare(strict_types=1);

namespace Database\Factories\Domain\ColdChain;

use App\Domain\ColdChain\Models\TemperatureReading;
use App\Domain\ColdChain\ValueObjects\Celsius;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemperatureReading>
 */
class TemperatureReadingFactory extends Factory
{
    protected $model = TemperatureReading::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'celsius' => new Celsius($this->faker->randomFloat(2, 2, 8)),
            'recorded_at' => now(),
        ];
    }
}
