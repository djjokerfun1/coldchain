<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Every endpoint under /api/v1 requires authentication, so every test here
 * needs a user. Defaulting to a planner keeps most tests focused on their
 * actual subject; tests exercising role scoping call actingAsDriver() or
 * actingAsClient() to switch to the role they're testing.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsPlanner();
    }

    protected function actingAsPlanner(): User
    {
        return $this->actingAsUser(User::factory()->planner());
    }

    protected function actingAsUser(UserFactory $factory): User
    {
        $user = $factory->create();
        Sanctum::actingAs($user, ['*']);

        return $user;
    }
}
