<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\TrackingEvent;
use App\Domain\Shipping\ValueObjects\GeoPoint;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrackingEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_has_no_updated_at_column(): void
    {
        $this->assertFalse(Schema::hasColumn('tracking_events', 'updated_at'));
    }

    public function test_position_is_composed_from_latitude_and_longitude(): void
    {
        $event = TrackingEvent::factory()->create(['latitude' => 51.9225, 'longitude' => 4.47917]);

        $this->assertEquals(new GeoPoint(51.9225, 4.47917), $event->position);
    }

    public function test_a_duplicate_external_event_id_for_the_same_shipment_is_rejected(): void
    {
        $shipment = Shipment::factory()->create();
        TrackingEvent::factory()->create(['shipment_id' => $shipment->id, 'external_event_id' => 'device-abc-1']);

        $this->expectException(QueryException::class);

        TrackingEvent::factory()->create(['shipment_id' => $shipment->id, 'external_event_id' => 'device-abc-1']);
    }

    public function test_the_same_external_event_id_is_allowed_across_different_shipments(): void
    {
        TrackingEvent::factory()->create(['external_event_id' => 'device-abc-1']);
        $event = TrackingEvent::factory()->create(['external_event_id' => 'device-abc-1']);

        $this->assertDatabaseHas('tracking_events', ['id' => $event->id]);
    }
}
