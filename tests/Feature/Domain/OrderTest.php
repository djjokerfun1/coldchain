<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\Ordering\Enums\OrderStatus;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\Models\OrderLine;
use App\Domain\Ordering\ValueObjects\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_addresses_persist_as_value_objects(): void
    {
        $order = Order::factory()->create();

        $fresh = $order->fresh();

        $this->assertInstanceOf(Address::class, $fresh->pickup_address);
        $this->assertInstanceOf(Address::class, $fresh->delivery_address);
    }

    public function test_status_casts_to_the_enum(): void
    {
        $order = Order::factory()->placed()->create();

        $this->assertSame(OrderStatus::Placed, $order->status);
        $this->assertNotNull($order->placed_at);
    }

    public function test_it_has_many_lines(): void
    {
        $order = Order::factory()->create();
        OrderLine::factory()->count(3)->create(['order_id' => $order->id]);

        $this->assertCount(3, $order->lines()->get());
    }

    public function test_deleting_a_client_is_blocked_while_orders_reference_it(): void
    {
        $order = Order::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $order->client->delete();
    }
}
