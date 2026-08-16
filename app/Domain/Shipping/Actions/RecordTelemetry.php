<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\ColdChain\ValueObjects\Celsius;
use App\Domain\Shipping\Enums\TrackingEventType;
use App\Domain\Shipping\Events\TelemetryRecorded;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\TrackingEvent;
use App\Domain\Shipping\ValueObjects\GeoPoint;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Devices retry pings they can't confirm were received, so the same
 * external_event_id can arrive more than once. Recording is idempotent:
 * a duplicate is absorbed silently, as a no-op, not an error — that's the
 * device's expected outcome, not a failure case.
 */
class RecordTelemetry
{
    /**
     * @return TrackingEvent|null the recorded event, or null if it was a duplicate
     */
    public function handle(
        Shipment $shipment,
        string $externalEventId,
        TrackingEventType $type,
        GeoPoint $position,
        CarbonImmutable $recordedAt,
        ?Celsius $temperature,
    ): ?TrackingEvent {
        // The catch has to sit outside DB::transaction(), not inside its
        // closure: once Postgres rejects one statement in a transaction, the
        // whole transaction is aborted and refuses further commands, so
        // catching in-closure and returning normally would make the
        // implicit commit that follows fail too.
        try {
            $event = DB::transaction(function () use ($shipment, $externalEventId, $type, $position, $recordedAt, $temperature): TrackingEvent {
                $trackingEvent = $shipment->trackingEvents()->create([
                    'external_event_id' => $externalEventId,
                    'type' => $type,
                    'position' => $position,
                    'recorded_at' => $recordedAt,
                ]);

                if ($temperature !== null) {
                    $shipment->temperatureReadings()->create([
                        'celsius' => $temperature,
                        'recorded_at' => $recordedAt,
                    ]);
                }

                return $trackingEvent;
            });
        } catch (UniqueConstraintViolationException) {
            // A retried ping with an external_event_id already on file:
            // the device's expected outcome, not an error.
            return null;
        }

        TelemetryRecorded::dispatch($event);

        return $event;
    }
}
