<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Models\User;

class ClientApiTest extends ApiTestCase
{
    public function test_it_lists_clients_paginated(): void
    {
        Client::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/clients');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_searches_clients_by_name(): void
    {
        Client::factory()->create(['name' => 'Acme Pharma']);
        Client::factory()->create(['name' => 'Globex Logistics']);

        $response = $this->getJson('/api/v1/clients?filter[name]=Acme');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Pharma');
    }

    public function test_it_creates_a_client(): void
    {
        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Acme Pharma',
            'email' => 'ops@acme.test',
            'phone' => '+31 10 1234567',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Acme Pharma');
        $this->assertDatabaseHas('clients', ['email' => 'ops@acme.test']);
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        Client::factory()->create(['email' => 'ops@acme.test']);

        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Another Client',
            'email' => 'ops@acme.test',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_it_rejects_a_missing_name(): void
    {
        $response = $this->postJson('/api/v1/clients', ['email' => 'ops@acme.test']);

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_it_shows_a_client(): void
    {
        $client = Client::factory()->create();

        $response = $this->getJson("/api/v1/clients/{$client->id}");

        $response->assertOk()->assertJsonPath('data.id', $client->id);
    }

    public function test_it_returns_404_for_a_missing_client(): void
    {
        $this->getJson('/api/v1/clients/999')->assertNotFound();
    }

    public function test_it_updates_a_client(): void
    {
        $client = Client::factory()->create(['name' => 'Old Name']);

        $response = $this->patchJson("/api/v1/clients/{$client->id}", ['name' => 'New Name']);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_it_deletes_a_client_without_orders(): void
    {
        $client = Client::factory()->create();

        $this->deleteJson("/api/v1/clients/{$client->id}")->assertNoContent();
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_it_refuses_to_delete_a_client_with_orders(): void
    {
        $client = Client::factory()->create();
        Order::factory()->create(['client_id' => $client->id]);

        $this->deleteJson("/api/v1/clients/{$client->id}")->assertConflict();
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_a_client_cannot_list_clients(): void
    {
        $this->actingAsUser(User::factory()->client());

        $this->getJson('/api/v1/clients')->assertForbidden();
    }

    public function test_a_client_can_view_their_own_record(): void
    {
        $client = Client::factory()->create();
        $this->actingAsUser(User::factory()->client($client));

        $this->getJson("/api/v1/clients/{$client->id}")->assertOk();
    }

    public function test_a_client_cannot_view_another_clients_record(): void
    {
        $otherClient = Client::factory()->create();
        $this->actingAsUser(User::factory()->client());

        $this->getJson("/api/v1/clients/{$otherClient->id}")->assertForbidden();
    }

    public function test_a_client_cannot_update_a_client(): void
    {
        $client = Client::factory()->create();
        $this->actingAsUser(User::factory()->client($client));

        $this->patchJson("/api/v1/clients/{$client->id}", ['name' => 'New Name'])->assertForbidden();
    }
}
