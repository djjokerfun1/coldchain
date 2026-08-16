<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\ColdChain\Enums\ExcursionStatus;
use App\Domain\ColdChain\Models\TemperatureExcursion;
use App\Domain\ColdChain\Models\TemperatureReading;
use App\Domain\ColdChain\ValueObjects\Celsius;
use App\Domain\Ordering\Enums\OrderStatus;
use App\Domain\Ordering\Enums\StorageClass;
use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\Models\OrderLine;
use App\Domain\Ordering\Models\Product;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\TrackingEventType;
use App\Domain\Shipping\Models\Driver;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $products = collect([
            Product::factory()->create(['storage_class' => StorageClass::Ambient]),
            Product::factory()->refrigerated()->create(),
            Product::factory()->frozen()->create(),
        ]);

        $drivers = Driver::factory()->count(2)->create();
        $vehicles = $drivers->map(fn (Driver $driver) => Vehicle::factory()->create(['driver_id' => $driver->id]));

        // A mix of end states, not five identical happy paths: a demo
        // dataset where every shipment is already delivered can't show live
        // tracking, and `fleet:simulate` needs shipments still in flight.
        $endStates = [
            ShipmentStatus::Delivered,
            ShipmentStatus::Delivered,
            ShipmentStatus::InTransit,
            ShipmentStatus::InTransit,
            ShipmentStatus::PickedUp,
        ];

        Client::factory()->count(5)->create()->each(function (Client $client, int $index) use ($products, $drivers, $vehicles, $endStates): void {
            $order = Order::factory()->placed()->create(['client_id' => $client->id]);

            $products->random(2)->each(fn (Product $product) => OrderLine::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
            ]));

            $shipment = Shipment::factory()->create([
                'order_id' => $order->id,
                'driver_id' => $drivers->random()->id,
                'vehicle_id' => $vehicles->random()->id,
            ]);

            $this->driveShipment($shipment, $endStates[$index]);
        });
    }

    /**
     * Walk a shipment through its real lifecycle rather than setting a
     * status directly, stopping at $endState, so the seed data exercises
     * the same transition guard and append-only logs the application uses
     * at runtime.
     */
    private function driveShipment(Shipment $shipment, ShipmentStatus $endState): void
    {
        $shipment->trackingEvents()->create([
            'type' => TrackingEventType::StatusChange,
            'recorded_at' => now(),
        ]);
        $shipment->transitionTo(ShipmentStatus::PickedUp);

        if ($endState === ShipmentStatus::PickedUp) {
            return;
        }

        $shipment->transitionTo(ShipmentStatus::InTransit);

        collect(range(1, 3))->each(function (int $step) use ($shipment): void {
            $shipment->trackingEvents()->create([
                'type' => TrackingEventType::GpsPing,
                'external_event_id' => "seed-{$shipment->id}-{$step}",
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'recorded_at' => now(),
            ]);

            TemperatureReading::factory()->create([
                'shipment_id' => $shipment->id,
                'celsius' => new Celsius(fake()->randomFloat(2, 2, 8)),
            ]);
        });

        // One shipment in three drifts out of range, to seed a candidate
        // excursion worth demonstrating.
        if (fake()->boolean(33)) {
            TemperatureReading::factory()->create([
                'shipment_id' => $shipment->id,
                'celsius' => new Celsius(11.0),
            ]);

            TemperatureExcursion::factory()->create([
                'shipment_id' => $shipment->id,
                'status' => ExcursionStatus::Candidate,
                'min_celsius' => new Celsius(2.0),
                'max_celsius' => new Celsius(8.0),
            ]);
        }

        if ($endState === ShipmentStatus::InTransit) {
            return;
        }

        $shipment->transitionTo(ShipmentStatus::Delivered);
        $shipment->order->update(['status' => OrderStatus::Fulfilled]);
    }
}
