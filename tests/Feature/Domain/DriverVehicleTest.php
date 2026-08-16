<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\Shipping\Models\Driver;
use App\Domain\Shipping\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverVehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_driver_can_have_multiple_vehicles(): void
    {
        $driver = Driver::factory()->create();
        Vehicle::factory()->count(2)->create(['driver_id' => $driver->id]);

        $this->assertCount(2, $driver->vehicles);
    }

    public function test_a_vehicle_can_exist_without_an_assigned_driver(): void
    {
        $vehicle = Vehicle::factory()->create(['driver_id' => null]);

        $this->assertNull($vehicle->driver);
    }

    public function test_removing_a_driver_does_not_delete_their_vehicles(): void
    {
        $driver = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id]);

        $driver->delete();

        $this->assertNull($vehicle->fresh()->driver_id);
    }
}
