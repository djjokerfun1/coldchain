<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Ordering\Enums\OrderStatus;
use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\Models\Product;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        $client = Client::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        return [
            'client_id' => $client->id,
            'pickup_address' => [
                'line1' => '1 Cold Storage Way',
                'city' => 'Rotterdam',
                'postal_code' => '3011AA',
                'country' => 'NL',
            ],
            'delivery_address' => [
                'line1' => '99 Pharma Ave',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country' => 'DE',
            ],
            'lines' => [
                ['product_id' => $productA->id, 'quantity' => 5],
                ['product_id' => $productB->id, 'quantity' => 2],
            ],
        ];
    }

    public function test_it_creates_an_order_with_its_lines_atomically(): void
    {
        $response = $this->postJson('/api/v1/orders', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.pickup_address.city', 'Rotterdam')
            ->assertJsonCount(2, 'data.lines');

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertCount(2, $order->lines);
    }

    public function test_an_unknown_product_in_a_line_prevents_order_creation(): void
    {
        $payload = $this->validPayload();
        $payload['lines'][] = ['product_id' => 999999, 'quantity' => 1];

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertUnprocessable();
        $this->assertSame(0, Order::count());
    }

    public function test_it_rejects_an_order_with_no_lines(): void
    {
        $payload = $this->validPayload();
        $payload['lines'] = [];

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors('lines');
    }

    public function test_it_rejects_an_unknown_client(): void
    {
        $payload = $this->validPayload();
        $payload['client_id'] = 999999;

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors('client_id');
    }

    public function test_it_filters_orders_by_status(): void
    {
        Order::factory()->create(['status' => OrderStatus::Draft]);
        Order::factory()->placed()->create();

        $response = $this->getJson('/api/v1/orders?filter[status]=placed');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_shows_an_order_with_its_client_and_lines(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk()->assertJsonPath('data.id', $order->id);
    }

    public function test_it_updates_the_delivery_address(): void
    {
        $order = Order::factory()->create();

        $response = $this->patchJson("/api/v1/orders/{$order->id}", [
            'delivery_address' => [
                'line1' => '2 New Street',
                'city' => 'Amsterdam',
                'postal_code' => '1011AB',
                'country' => 'NL',
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.delivery_address.city', 'Amsterdam');
    }

    public function test_it_refuses_to_delete_an_order_with_shipments(): void
    {
        $order = Order::factory()->create();
        Shipment::factory()->create(['order_id' => $order->id]);

        $this->deleteJson("/api/v1/orders/{$order->id}")->assertConflict();
    }

    public function test_it_deletes_an_order_without_shipments(): void
    {
        $order = Order::factory()->create();

        $this->deleteJson("/api/v1/orders/{$order->id}")->assertNoContent();
    }
}
