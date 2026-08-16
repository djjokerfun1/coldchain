<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Shipping\Models\Shipment;
use App\Enums\UserRole;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return match ($user->role) {
            UserRole::Planner => true,
            UserRole::Driver => $user->driver_id === $shipment->driver_id,
            UserRole::Client => $user->client_id === $shipment->order->client_id,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->role === UserRole::Planner;
    }

    /**
     * A device isn't a user in this system yet, so telemetry is posted on
     * its behalf by whoever is physically running it: a planner, or the
     * driver the shipment is actually assigned to.
     */
    public function recordTelemetry(User $user, Shipment $shipment): bool
    {
        return $user->role === UserRole::Planner
            || ($user->role === UserRole::Driver && $user->driver_id === $shipment->driver_id);
    }
}
