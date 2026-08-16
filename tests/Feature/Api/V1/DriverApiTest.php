<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Shipping\Models\Driver;
use App\Models\User;

class DriverApiTest extends ApiTestCase
{
    public function test_a_planner_lists_drivers(): void
    {
        Driver::factory()->count(3)->create();

        $this->getJson('/api/v1/drivers')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_a_planner_creates_a_driver(): void
    {
        $response = $this->postJson('/api/v1/drivers', [
            'name' => 'Jane Doe',
            'license_number' => 'DL-1234567',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Jane Doe');
    }

    public function test_a_driver_cannot_list_drivers(): void
    {
        $this->actingAsUser(User::factory()->driver());

        $this->getJson('/api/v1/drivers')->assertForbidden();
    }

    public function test_a_driver_can_view_their_own_record(): void
    {
        $driver = Driver::factory()->create();
        $this->actingAsUser(User::factory()->driver($driver));

        $this->getJson("/api/v1/drivers/{$driver->id}")->assertOk();
    }

    public function test_a_driver_cannot_view_another_drivers_record(): void
    {
        $otherDriver = Driver::factory()->create();
        $this->actingAsUser(User::factory()->driver());

        $this->getJson("/api/v1/drivers/{$otherDriver->id}")->assertForbidden();
    }

    public function test_a_driver_cannot_create_a_driver(): void
    {
        $this->actingAsUser(User::factory()->driver());

        $this->postJson('/api/v1/drivers', [
            'name' => 'Jane Doe',
            'license_number' => 'DL-1234567',
        ])->assertForbidden();
    }

    public function test_deleting_a_driver_does_not_delete_their_vehicles_or_shipments(): void
    {
        $driver = Driver::factory()->create();

        $this->deleteJson("/api/v1/drivers/{$driver->id}")->assertNoContent();
    }
}
