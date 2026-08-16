<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deliberately extends TestCase, not ApiTestCase: these tests are about
 * establishing (or failing to establish) identity in the first place, so
 * they need to control authentication themselves rather than start
 * pre-authenticated as a planner.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_an_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/clients')->assertUnauthorized();
    }

    public function test_it_issues_a_token_for_valid_credentials(): void
    {
        User::factory()->planner()->create(['email' => 'ops@coldchain.test', 'password' => 'correct-password']);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'ops@coldchain.test',
            'password' => 'correct-password',
        ]);

        $response->assertCreated()->assertJsonPath('role', UserRole::Planner->value);
        $this->assertIsString($response->json('token'));
    }

    public function test_it_rejects_a_wrong_password(): void
    {
        User::factory()->planner()->create(['email' => 'ops@coldchain.test', 'password' => 'correct-password']);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'ops@coldchain.test',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_it_rejects_an_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nobody@coldchain.test',
            'password' => 'whatever',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_the_issued_token_authenticates_subsequent_requests(): void
    {
        User::factory()->planner()->create(['email' => 'ops@coldchain.test', 'password' => 'correct-password']);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'ops@coldchain.test',
            'password' => 'correct-password',
        ])->json('token');

        $this->withToken($token)->getJson('/api/v1/clients')->assertOk();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        // Sanctum::actingAs() mocks the token rather than persisting one,
        // which would make revocation a no-op to assert on. This test needs
        // a real token, exactly like a client would hold after logging in.
        $user = User::factory()->planner()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/logout')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }
}
