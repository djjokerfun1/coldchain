<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Domain\Shipping\Models\Driver;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\TrackingEvent;
use App\Models\User;

class ShipmentApiTest extends ApiTestCase
{
    public function test_a_planner_creates_a_shipment_for_an_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->postJson('/api/v1/shipments', ['order_id' => $order->id]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.order.id', $order->id);
    }

    public function test_a_planner_assigns_a_driver_and_vehicle_via_update(): void
    {
        $shipment = Shipment::factory()->create();
        $driver = Driver::factory()->create();

        $response = $this->patchJson("/api/v1/shipments/{$shipment->id}", ['driver_id' => $driver->id]);

        $response->assertOk()->assertJsonPath('data.driver.id', $driver->id);
    }

    public function test_a_planner_sees_every_shipment_in_the_index(): void
    {
        Shipment::factory()->count(3)->create();

        $this->getJson('/api/v1/shipments')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_a_driver_only_sees_shipments_assigned_to_them(): void
    {
        $driver = Driver::factory()->create();
        Shipment::factory()->count(2)->create(['driver_id' => $driver->id]);
        Shipment::factory()->count(3)->create();

        $this->actingAsUser(User::factory()->driver($driver));

        $this->getJson('/api/v1/shipments')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_driver_cannot_view_a_shipment_assigned_to_someone_else(): void
    {
        $shipment = Shipment::factory()->create(['driver_id' => Driver::factory()->create()->id]);
        $this->actingAsUser(User::factory()->driver());

        $this->getJson("/api/v1/shipments/{$shipment->id}")->assertForbidden();
    }

    public function test_a_client_only_sees_shipments_on_their_own_orders(): void
    {
        $client = Client::factory()->create();
        $ownOrder = Order::factory()->create(['client_id' => $client->id]);
        Shipment::factory()->count(2)->create(['order_id' => $ownOrder->id]);
        Shipment::factory()->count(3)->create();

        $this->actingAsUser(User::factory()->client($client));

        $this->getJson('/api/v1/shipments')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_client_cannot_view_a_shipment_on_someone_elses_order(): void
    {
        $shipment = Shipment::factory()->create();
        $this->actingAsUser(User::factory()->client());

        $this->getJson("/api/v1/shipments/{$shipment->id}")->assertForbidden();
    }

    public function test_a_driver_cannot_create_or_update_a_shipment(): void
    {
        $order = Order::factory()->create();
        $shipment = Shipment::factory()->create();
        $this->actingAsUser(User::factory()->driver());

        $this->postJson('/api/v1/shipments', ['order_id' => $order->id])->assertForbidden();
        $this->patchJson("/api/v1/shipments/{$shipment->id}", ['driver_id' => null])->assertForbidden();
    }

    public function test_it_refuses_to_delete_a_shipment_with_tracking_history(): void
    {
        $shipment = Shipment::factory()->create();
        TrackingEvent::factory()->create(['shipment_id' => $shipment->id]);

        $this->deleteJson("/api/v1/shipments/{$shipment->id}")->assertConflict();
    }

    public function test_it_deletes_a_shipment_with_no_tracking_history(): void
    {
        $shipment = Shipment::factory()->create();

        $this->deleteJson("/api/v1/shipments/{$shipment->id}")->assertNoContent();
    }
}
