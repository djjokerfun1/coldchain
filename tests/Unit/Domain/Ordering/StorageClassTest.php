<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ordering;

use App\Domain\ColdChain\ValueObjects\Celsius;
use App\Domain\Ordering\Enums\StorageClass;
use PHPUnit\Framework\TestCase;

class StorageClassTest extends TestCase
{
    public function test_ambient_has_no_temperature_range(): void
    {
        $this->assertNull(StorageClass::Ambient->temperatureRange());
    }

    public function test_refrigerated_range_matches_gdp_2_to_8(): void
    {
        $range = StorageClass::Refrigerated->temperatureRange();

        $this->assertNotNull($range);
        $this->assertTrue($range->contains(new Celsius(5.0)));
        $this->assertFalse($range->contains(new Celsius(9.0)));
    }

    public function test_frozen_range_matches_minus_25_to_minus_15(): void
    {
        $range = StorageClass::Frozen->temperatureRange();

        $this->assertNotNull($range);
        $this->assertTrue($range->contains(new Celsius(-20.0)));
        $this->assertFalse($range->contains(new Celsius(-10.0)));
    }
}
