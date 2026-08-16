<?php

declare(strict_types=1);

namespace Database\Factories\Domain\ColdChain;

use App\Domain\ColdChain\Enums\ExcursionStatus;
use App\Domain\ColdChain\Models\TemperatureExcursion;
use App\Domain\ColdChain\ValueObjects\Celsius;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemperatureExcursion>
 */
class TemperatureExcursionFactory extends Factory
{
    protected $model = TemperatureExcursion::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'status' => ExcursionStatus::Candidate,
            'min_celsius' => new Celsius(9.0),
            'max_celsius' => new Celsius(11.5),
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => ExcursionStatus::Resolved,
            'closed_at' => now(),
        ]);
    }
}
