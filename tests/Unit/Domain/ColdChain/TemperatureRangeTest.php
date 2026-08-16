<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ColdChain;

use App\Domain\ColdChain\ValueObjects\Celsius;
use App\Domain\ColdChain\ValueObjects\TemperatureRange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TemperatureRangeTest extends TestCase
{
    public function test_it_rejects_an_inverted_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TemperatureRange(new Celsius(8.0), new Celsius(2.0));
    }

    public function test_it_contains_readings_within_bounds_inclusive(): void
    {
        $range = new TemperatureRange(new Celsius(2.0), new Celsius(8.0));

        $this->assertTrue($range->contains(new Celsius(2.0)));
        $this->assertTrue($range->contains(new Celsius(8.0)));
        $this->assertTrue($range->contains(new Celsius(5.0)));
    }

    public function test_it_excludes_readings_outside_bounds(): void
    {
        $range = new TemperatureRange(new Celsius(2.0), new Celsius(8.0));

        $this->assertFalse($range->contains(new Celsius(1.9)));
        $this->assertFalse($range->contains(new Celsius(8.1)));
    }
}
