<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\Auditing\Models\AuditEntry;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_has_no_updated_at_column(): void
    {
        $this->assertFalse(Schema::hasColumn('audit_entries', 'updated_at'));
    }

    public function test_it_resolves_the_auditable_shipment(): void
    {
        $shipment = Shipment::factory()->create();
        $entry = AuditEntry::factory()->create([
            'auditable_type' => Shipment::class,
            'auditable_id' => $shipment->id,
        ]);

        $this->assertTrue($entry->auditable->is($shipment));
    }

    public function test_data_casts_to_an_array(): void
    {
        $entry = AuditEntry::factory()->create(['data' => ['from' => 'pending', 'to' => 'picked_up']]);

        // jsonb does not preserve key order, unlike json, so compare by content.
        $this->assertEqualsCanonicalizing(['from' => 'pending', 'to' => 'picked_up'], $entry->fresh()->data);
    }
}
