<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Events;

use App\Domain\Shipping\Models\TrackingEvent;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired only for a genuinely new tracking event, never for a duplicate ping
 * the idempotency check absorbed silently.
 */
class TelemetryRecorded
{
    use Dispatchable;

    public function __construct(public readonly TrackingEvent $trackingEvent) {}
}
