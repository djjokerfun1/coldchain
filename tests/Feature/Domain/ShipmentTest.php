<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Exceptions\InvalidStatusTransition;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_transition_updates_the_status(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);

        $shipment->transitionTo(ShipmentStatus::PickedUp);

        $this->assertSame(ShipmentStatus::PickedUp, $shipment->fresh()->status);
    }

    public function test_an_invalid_transition_is_rejected_and_persists_nothing(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Delivered]);

        try {
            $shipment->transitionTo(ShipmentStatus::PickedUp);
            $this->fail('Expected InvalidStatusTransition to be thrown.');
        } catch (InvalidStatusTransition $exception) {
            $this->assertSame(ShipmentStatus::Delivered, $exception->from);
            $this->assertSame(ShipmentStatus::PickedUp, $exception->to);
        }

        $this->assertSame(ShipmentStatus::Delivered, $shipment->fresh()->status);
    }

    public function test_it_belongs_to_an_order(): void
    {
        $shipment = Shipment::factory()->create();

        $this->assertNotNull($shipment->order);
    }
}
