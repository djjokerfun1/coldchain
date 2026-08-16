<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ColdChain;

use App\Domain\ColdChain\ValueObjects\Celsius;
use PHPUnit\Framework\TestCase;

class CelsiusTest extends TestCase
{
    public function test_it_compares_values(): void
    {
        $this->assertTrue((new Celsius(2.0))->isBelow(new Celsius(8.0)));
        $this->assertTrue((new Celsius(9.0))->isAbove(new Celsius(8.0)));
        $this->assertFalse((new Celsius(5.0))->isBelow(new Celsius(5.0)));
    }

    public function test_equality_tolerates_floating_point_noise(): void
    {
        $this->assertTrue((new Celsius(2.0))->equals(new Celsius(2.0 + 1e-6)));
        $this->assertFalse((new Celsius(2.0))->equals(new Celsius(2.5)));
    }

    public function test_string_representation(): void
    {
        $this->assertSame('2.0°C', (string) new Celsius(2.0));
        $this->assertSame('-15.5°C', (string) new Celsius(-15.5));
    }
}
