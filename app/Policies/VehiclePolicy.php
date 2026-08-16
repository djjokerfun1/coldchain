<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Shipping\Models\Vehicle;
use App\Enums\UserRole;
use App\Models\User;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->role === UserRole::Planner
            || ($user->role === UserRole::Driver && $user->driver_id === $vehicle->driver_id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->role === UserRole::Planner;
    }
}
