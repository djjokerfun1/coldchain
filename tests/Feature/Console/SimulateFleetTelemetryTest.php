<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SimulateFleetTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fails_without_a_planner_user(): void
    {
        $this->artisan('fleet:simulate')
            ->assertExitCode(1)
            ->expectsOutputToContain('No planner user found');
    }

    public function test_it_fails_without_an_active_shipment(): void
    {
        User::factory()->planner()->create();
        Shipment::factory()->create(['status' => ShipmentStatus::Delivered]);

        $this->artisan('fleet:simulate')
            ->assertExitCode(1)
            ->expectsOutputToContain('No shipments are picked up or in transit');
    }

    public function test_it_posts_one_ping_per_vehicle_per_tick(): void
    {
        User::factory()->planner()->create();
        Shipment::factory()->count(2)->create(['status' => ShipmentStatus::InTransit]);
        Http::fake(['*/telemetry' => Http::response(['data' => []], 202)]);

        $this->artisan('fleet:simulate --vehicles=2 --ticks=3 --interval=0')->assertExitCode(0);

        Http::assertSentCount(6);
    }

    public function test_a_100_percent_packet_loss_sends_nothing(): void
    {
        User::factory()->planner()->create();
        Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);
        Http::fake();

        $this->artisan('fleet:simulate --vehicles=1 --ticks=3 --interval=0 --packet-loss=100')
            ->assertExitCode(0)
            ->expectsOutputToContain('packet lost');

        Http::assertNothingSent();
    }

    public function test_a_100_percent_duplicate_rate_repeats_the_first_payload_exactly(): void
    {
        User::factory()->planner()->create();
        Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);
        Http::fake(['*/telemetry' => Http::response(['data' => []], 202)]);

        $this->artisan('fleet:simulate --vehicles=1 --ticks=3 --interval=0 --duplicate-rate=100')
            ->assertExitCode(0);

        $bodies = collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0]->data()['external_event_id']);

        $this->assertCount(3, $bodies);
        $this->assertCount(1, $bodies->unique());
    }

    public function test_it_deletes_its_service_token_after_running(): void
    {
        User::factory()->planner()->create();
        Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);
        Http::fake(['*/telemetry' => Http::response(['data' => []], 202)]);

        $this->artisan('fleet:simulate --vehicles=1 --ticks=1 --interval=0')->assertExitCode(0);

        $this->assertSame(0, PersonalAccessToken::where('name', 'fleet-simulator')->count());
    }
}
