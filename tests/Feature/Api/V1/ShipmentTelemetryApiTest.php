<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Auditing\Models\AuditEntry;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Driver;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\TrackingEvent;
use App\Models\User;

class ShipmentTelemetryApiTest extends ApiTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'external_event_id' => 'device-abc-1',
            'latitude' => 51.9225,
            'longitude' => 4.47917,
        ], $overrides);
    }

    public function test_it_records_a_new_ping_and_updates_the_shipment_position(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);

        $response = $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload());

        $response->assertAccepted()->assertJsonPath('data.external_event_id', 'device-abc-1');

        $this->assertDatabaseCount('tracking_events', 1);

        $shipment->refresh();
        $this->assertSame(51.9225, $shipment->current_latitude);
        $this->assertSame(4.47917, $shipment->current_longitude);
        $this->assertNotNull($shipment->last_ping_at);
    }

    public function test_a_duplicate_ping_is_a_no_op(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertAccepted();
        $response = $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload());

        $response->assertAccepted()->assertJsonPath('status', 'duplicate');
        $this->assertDatabaseCount('tracking_events', 1);
        $this->assertSame(1, AuditEntry::count());
    }

    public function test_it_records_an_audit_entry_for_the_ping(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertAccepted();

        $this->assertDatabaseHas('audit_entries', [
            'auditable_type' => Shipment::class,
            'auditable_id' => $shipment->id,
            'action' => 'telemetry_recorded',
        ]);
    }

    public function test_a_gps_ping_advances_a_picked_up_shipment_into_transit(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::PickedUp]);

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertAccepted();

        $this->assertSame(ShipmentStatus::InTransit, $shipment->refresh()->status);
    }

    public function test_a_gps_ping_does_not_move_a_pending_shipment_out_of_pending(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertAccepted();

        $this->assertSame(ShipmentStatus::Pending, $shipment->refresh()->status);
    }

    public function test_it_records_a_temperature_reading_when_provided(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);

        $this->postJson(
            "/api/v1/shipments/{$shipment->id}/telemetry",
            $this->payload(['temperature_celsius' => 5.5]),
        )->assertAccepted();

        $this->assertDatabaseCount('temperature_readings', 1);
        $this->assertDatabaseHas('temperature_readings', ['shipment_id' => $shipment->id]);
    }

    public function test_it_does_not_record_a_temperature_reading_when_omitted(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertAccepted();

        $this->assertDatabaseCount('temperature_readings', 0);
    }

    public function test_it_rejects_a_ping_missing_coordinates(): void
    {
        $shipment = Shipment::factory()->create();

        $response = $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", [
            'external_event_id' => 'device-abc-1',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_a_driver_can_record_telemetry_for_their_own_shipment(): void
    {
        $driver = Driver::factory()->create();
        $shipment = Shipment::factory()->create(['driver_id' => $driver->id, 'status' => ShipmentStatus::InTransit]);
        $this->actingAsUser(User::factory()->driver($driver));

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertAccepted();
    }

    public function test_a_driver_cannot_record_telemetry_for_someone_elses_shipment(): void
    {
        $shipment = Shipment::factory()->create(['driver_id' => Driver::factory()->create()->id]);
        $this->actingAsUser(User::factory()->driver());

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertForbidden();
    }

    public function test_a_client_cannot_record_telemetry(): void
    {
        $shipment = Shipment::factory()->create();
        $this->actingAsUser(User::factory()->client());

        $this->postJson("/api/v1/shipments/{$shipment->id}/telemetry", $this->payload())->assertForbidden();
    }

    public function test_the_same_external_event_id_is_independent_per_shipment(): void
    {
        $first = Shipment::factory()->create();
        $second = Shipment::factory()->create();

        $this->postJson("/api/v1/shipments/{$first->id}/telemetry", $this->payload())->assertAccepted();
        $response = $this->postJson("/api/v1/shipments/{$second->id}/telemetry", $this->payload());

        $response->assertAccepted()->assertJsonMissingPath('status');
        $this->assertSame(2, TrackingEvent::count());
    }
}
