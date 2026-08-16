<?php

declare(strict_types=1);

namespace App\Domain\Shipping\ValueObjects;

use InvalidArgumentException;

final readonly class GeoPoint
{
    public function __construct(public float $latitude, public float $longitude)
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException("Latitude {$latitude} is out of range.");
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException("Longitude {$longitude} is out of range.");
        }
    }

    public function __toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }
}
