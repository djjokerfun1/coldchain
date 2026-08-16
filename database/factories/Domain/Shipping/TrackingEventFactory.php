<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Shipping;

use App\Domain\Shipping\Enums\TrackingEventType;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    protected $model = TrackingEvent::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'type' => TrackingEventType::GpsPing,
            'external_event_id' => $this->faker->uuid(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'payload' => null,
            'recorded_at' => now(),
        ];
    }
}
