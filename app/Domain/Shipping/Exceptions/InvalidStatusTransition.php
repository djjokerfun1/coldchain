<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Exceptions;

use App\Domain\Shipping\Enums\ShipmentStatus;
use RuntimeException;

class InvalidStatusTransition extends RuntimeException
{
    public function __construct(public readonly ShipmentStatus $from, public readonly ShipmentStatus $to)
    {
        parent::__construct("Shipment cannot transition from [{$from->value}] to [{$to->value}].");
    }
}
