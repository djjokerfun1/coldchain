<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sends real HTTP requests to the running API rather than calling
 * RecordTelemetry directly: the point of a device simulator is to exercise
 * the whole ingestion path — auth, the recordTelemetry policy, request
 * validation, idempotency, the queued listeners — not just the domain
 * method in isolation. Run from the host (`php artisan fleet:simulate`),
 * the same way `php artisan test` is: APP_URL resolves to the published
 * nginx port from there, not from inside the app container.
 */
class SimulateFleetTelemetry extends Command
{
    protected $signature = 'fleet:simulate
        {--vehicles=5 : how many active shipments to simulate}
        {--ticks=5 : how many pings each simulated vehicle sends}
        {--interval=1 : seconds to sleep between ticks}
        {--packet-loss=0 : percent chance a ping is dropped before it is ever sent}
        {--duplicate-rate=0 : percent chance a tick resends the previous ping unchanged}';

    protected $description = 'Simulate on-vehicle devices posting telemetry to the tracking API';

    public function handle(): int
    {
        $planner = User::where('role', UserRole::Planner)->first();

        if ($planner === null) {
            $this->error('No planner user found. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $shipments = Shipment::query()
            ->whereIn('status', [ShipmentStatus::PickedUp, ShipmentStatus::InTransit])
            ->inRandomOrder()
            ->limit((int) $this->option('vehicles'))
            ->get();

        if ($shipments->isEmpty()) {
            $this->error('No shipments are picked up or in transit. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $token = $planner->createToken('fleet-simulator', [UserRole::Planner->value])->plainTextToken;

        try {
            return $this->simulate($shipments->all(), $token);
        } finally {
            $planner->tokens()->where('name', 'fleet-simulator')->delete();
        }
    }

    /**
     * @param  list<Shipment>  $shipments
     */
    private function simulate(array $shipments, string $token): int
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $ticks = (int) $this->option('ticks');
        $interval = (int) $this->option('interval');
        $packetLoss = (int) $this->option('packet-loss');
        $duplicateRate = (int) $this->option('duplicate-rate');

        $vehicles = array_map(fn (Shipment $shipment): array => [
            'shipment' => $shipment,
            'position' => $this->startingPosition(),
            'lastPayload' => null,
        ], $shipments);

        $stats = ['sent' => 0, 'duplicate' => 0, 'lost' => 0, 'failed' => 0];

        $this->info(sprintf('Simulating %d vehicle(s) over %d tick(s)...', count($vehicles), $ticks));

        for ($tick = 1; $tick <= $ticks; $tick++) {
            foreach ($vehicles as &$vehicle) {
                $this->sendOneTick($vehicle, $tick, $baseUrl, $token, $packetLoss, $duplicateRate, $stats);
            }
            unset($vehicle);

            if ($tick < $ticks && $interval > 0) {
                sleep($interval);
            }
        }

        $this->newLine();
        $this->table(
            ['sent', 'duplicate', 'lost (never sent)', 'failed'],
            [[$stats['sent'], $stats['duplicate'], $stats['lost'], $stats['failed']]],
        );

        return self::SUCCESS;
    }

    /**
     * @param  array{shipment: Shipment, position: array{lat: float, lng: float}, lastPayload: array<string, mixed>|null}  $vehicle
     * @param  array{sent: int, duplicate: int, lost: int, failed: int}  $stats
     */
    private function sendOneTick(
        array &$vehicle,
        int $tick,
        string $baseUrl,
        string $token,
        int $packetLoss,
        int $duplicateRate,
        array &$stats,
    ): void {
        $shipment = $vehicle['shipment'];

        if (random_int(1, 100) <= $packetLoss) {
            $stats['lost']++;
            $this->line("  [tick {$tick}] {$shipment->reference}: packet lost");

            return;
        }

        $repeatsPrevious = $vehicle['lastPayload'] !== null && random_int(1, 100) <= $duplicateRate;

        if ($repeatsPrevious) {
            $payload = $vehicle['lastPayload'];
        } else {
            $vehicle['position'] = $this->wander($vehicle['position']);
            $payload = [
                'external_event_id' => (string) Str::uuid(),
                'latitude' => $vehicle['position']['lat'],
                'longitude' => $vehicle['position']['lng'],
                'temperature_celsius' => round(5.0 + $this->driftFraction() * 3, 2),
            ];
            $vehicle['lastPayload'] = $payload;
        }

        $response = Http::withToken($token)->post("{$baseUrl}/api/v1/shipments/{$shipment->id}/telemetry", $payload);

        if (! $response->successful()) {
            $stats['failed']++;
            $this->error("  [tick {$tick}] {$shipment->reference}: HTTP {$response->status()}");

            return;
        }

        $outcome = $response->json('status') === 'duplicate' ? 'duplicate' : 'sent';
        $stats[$outcome]++;

        $this->line(sprintf(
            '  [tick %d] %s: %s (%.5f, %.5f)%s',
            $tick,
            $shipment->reference,
            $outcome,
            $payload['latitude'],
            $payload['longitude'],
            $repeatsPrevious ? ' [replayed]' : '',
        ));
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function startingPosition(): array
    {
        return [
            'lat' => 50.0 + $this->driftFraction() * 5,
            'lng' => 5.0 + $this->driftFraction() * 5,
        ];
    }

    /**
     * @param  array{lat: float, lng: float}  $position
     * @return array{lat: float, lng: float}
     */
    private function wander(array $position): array
    {
        return [
            'lat' => $position['lat'] + $this->driftFraction() * 0.05,
            'lng' => $position['lng'] + $this->driftFraction() * 0.05,
        ];
    }

    private function driftFraction(): float
    {
        return random_int(-100, 100) / 100;
    }
}
