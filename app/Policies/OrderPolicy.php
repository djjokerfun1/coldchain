<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Ordering\Models\Order;
use App\Enums\UserRole;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Planner || $user->role === UserRole::Client;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->role === UserRole::Planner
            || ($user->role === UserRole::Client && $user->client_id === $order->client_id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->role === UserRole::Planner;
    }
}
