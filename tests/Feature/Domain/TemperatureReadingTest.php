<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\ColdChain\Models\TemperatureReading;
use App\Domain\ColdChain\ValueObjects\Celsius;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TemperatureReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_has_no_updated_at_column(): void
    {
        $this->assertFalse(Schema::hasColumn('temperature_readings', 'updated_at'));
    }

    public function test_celsius_casts_to_the_value_object(): void
    {
        $reading = TemperatureReading::factory()->create(['celsius' => new Celsius(5.5)]);

        $fresh = $reading->fresh();

        $this->assertInstanceOf(Celsius::class, $fresh->celsius);
        $this->assertTrue($fresh->celsius->equals(new Celsius(5.5)));
    }

    public function test_it_belongs_to_a_shipment(): void
    {
        $reading = TemperatureReading::factory()->create();

        $this->assertNotNull($reading->shipment);
    }
}
