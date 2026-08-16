<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum TrackingEventType: string
{
    case GpsPing = 'gps_ping';
    case StatusChange = 'status_change';
    case ExceptionRaised = 'exception_raised';
}
