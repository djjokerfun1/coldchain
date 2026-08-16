<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Ordering\Models\Client;
use App\Enums\UserRole;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function view(User $user, Client $client): bool
    {
        return $user->role === UserRole::Planner
            || ($user->role === UserRole::Client && $user->client_id === $client->id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function update(User $user, Client $client): bool
    {
        return $user->role === UserRole::Planner;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->role === UserRole::Planner;
    }
}
