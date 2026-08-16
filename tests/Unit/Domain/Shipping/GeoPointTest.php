<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shipping;

use App\Domain\Shipping\ValueObjects\GeoPoint;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GeoPointTest extends TestCase
{
    public function test_it_accepts_valid_coordinates(): void
    {
        $point = new GeoPoint(51.9225, 4.47917);

        $this->assertSame(51.9225, $point->latitude);
        $this->assertSame(4.47917, $point->longitude);
    }

    public function test_it_rejects_a_latitude_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GeoPoint(91.0, 0.0);
    }

    public function test_it_rejects_a_longitude_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GeoPoint(0.0, -181.0);
    }
}
