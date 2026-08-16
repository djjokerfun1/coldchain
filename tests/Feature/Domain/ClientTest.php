<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\Ordering\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_created_via_its_factory(): void
    {
        $client = Client::factory()->create(['name' => 'Acme Pharma']);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Acme Pharma',
        ]);
    }

    public function test_email_is_unique(): void
    {
        Client::factory()->create(['email' => 'ops@acme.test']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Client::factory()->create(['email' => 'ops@acme.test']);
    }
}
