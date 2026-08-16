<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Shipping\Models\Driver;
use App\Domain\Shipping\Models\Vehicle;
use App\Models\User;

class VehicleApiTest extends ApiTestCase
{
    public function test_a_planner_creates_a_vehicle(): void
    {
        $driver = Driver::factory()->create();

        $response = $this->postJson('/api/v1/vehicles', [
            'registration' => 'AB-123-CD',
            'driver_id' => $driver->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.driver.id', $driver->id);
    }

    public function test_a_vehicle_can_be_created_without_a_driver(): void
    {
        $response = $this->postJson('/api/v1/vehicles', ['registration' => 'AB-123-CD']);

        $response->assertCreated()->assertJsonPath('data.driver.id', null);
    }

    public function test_it_rejects_a_duplicate_registration(): void
    {
        Vehicle::factory()->create(['registration' => 'AB-123-CD']);

        $response = $this->postJson('/api/v1/vehicles', ['registration' => 'AB-123-CD']);

        $response->assertUnprocessable()->assertJsonValidationErrors('registration');
    }

    public function test_a_planner_reassigns_a_vehicle_to_a_different_driver(): void
    {
        $vehicle = Vehicle::factory()->create();
        $newDriver = Driver::factory()->create();

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", ['driver_id' => $newDriver->id]);

        $response->assertOk()->assertJsonPath('data.driver.id', $newDriver->id);
    }

    public function test_a_driver_can_view_their_own_vehicle(): void
    {
        $driver = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id]);
        $this->actingAsUser(User::factory()->driver($driver));

        $this->getJson("/api/v1/vehicles/{$vehicle->id}")->assertOk();
    }

    public function test_a_driver_cannot_view_a_vehicle_assigned_to_someone_else(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAsUser(User::factory()->driver());

        $this->getJson("/api/v1/vehicles/{$vehicle->id}")->assertForbidden();
    }
}
