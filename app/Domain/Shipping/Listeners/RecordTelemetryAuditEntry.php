<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Listeners;

use App\Domain\Auditing\Models\AuditEntry;
use App\Domain\Shipping\Events\TelemetryRecorded;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordTelemetryAuditEntry implements ShouldQueue
{
    public function handle(TelemetryRecorded $event): void
    {
        $trackingEvent = $event->trackingEvent;

        AuditEntry::create([
            'auditable_type' => Shipment::class,
            'auditable_id' => $trackingEvent->shipment_id,
            'action' => 'telemetry_recorded',
            'data' => [
                'tracking_event_id' => $trackingEvent->id,
                'type' => $trackingEvent->type->value,
                'external_event_id' => $trackingEvent->external_event_id,
            ],
            'occurred_at' => $trackingEvent->recorded_at,
        ]);
    }
}
