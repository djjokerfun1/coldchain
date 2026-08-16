<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Shipping\Models\Driver;
use App\Enums\UserRole;
use App\Models\User;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->role === UserRole::Planner
            || ($user->role === UserRole::Driver && $user->driver_id === $driver->id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->role === UserRole::Planner;
    }
}
