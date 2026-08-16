<?php

declare(strict_types=1);

namespace App\Domain\ColdChain\ValueObjects;

use InvalidArgumentException;

final readonly class TemperatureRange
{
    public function __construct(public Celsius $min, public Celsius $max)
    {
        if ($min->isAbove($max)) {
            throw new InvalidArgumentException('Temperature range minimum cannot exceed its maximum.');
        }
    }

    public function contains(Celsius $reading): bool
    {
        return ! $reading->isBelow($this->min) && ! $reading->isAbove($this->max);
    }

    public function __toString(): string
    {
        return "{$this->min} – {$this->max}";
    }
}
