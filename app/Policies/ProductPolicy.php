<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Ordering\Models\Product;
use App\Enums\UserRole;
use App\Models\User;

/**
 * Products are reference data every role needs to read (a driver or client
 * placing or inspecting an order needs to see what they're shipping), so
 * only the write actions are restricted to planners.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->role === UserRole::Planner;
    }
}
