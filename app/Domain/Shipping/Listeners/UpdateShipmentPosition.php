<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Listeners;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\TrackingEventType;
use App\Domain\Shipping\Events\TelemetryRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateShipmentPosition implements ShouldQueue
{
    public function handle(TelemetryRecorded $event): void
    {
        $trackingEvent = $event->trackingEvent;
        $shipment = $trackingEvent->shipment;
        $position = $trackingEvent->position;

        if ($position !== null) {
            // current_latitude/longitude/last_ping_at are never user-settable,
            // so they're deliberately outside $fillable; forceFill documents
            // that this is system state, not an accidental mass-assignment gap.
            $shipment->forceFill([
                'current_latitude' => $position->latitude,
                'current_longitude' => $position->longitude,
                'last_ping_at' => $trackingEvent->recorded_at,
            ])->save();
        }

        // A GPS ping while picked up is read as "the vehicle has left the
        // pickup point" and advances the shipment into transit. Pending ->
        // picked up is not inferred from telemetry; that's a checkpoint a
        // human confirms, not something a ping alone establishes.
        if ($trackingEvent->type === TrackingEventType::GpsPing && $shipment->status === ShipmentStatus::PickedUp) {
            $shipment->transitionTo(ShipmentStatus::InTransit);
        }
    }
}
