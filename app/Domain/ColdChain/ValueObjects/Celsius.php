<?php

declare(strict_types=1);

namespace App\Domain\ColdChain\ValueObjects;

final readonly class Celsius
{
    public function __construct(public float $value) {}

    public function isBelow(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function isAbove(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.001;
    }

    public function __toString(): string
    {
        return number_format($this->value, 1).'°C';
    }
}
