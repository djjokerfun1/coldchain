<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Auditing;

use App\Domain\Auditing\Models\AuditEntry;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEntry>
 */
class AuditEntryFactory extends Factory
{
    protected $model = AuditEntry::class;

    public function definition(): array
    {
        return [
            'auditable_type' => Shipment::class,
            'auditable_id' => Shipment::factory(),
            'action' => 'status_changed',
            'data' => ['from' => 'pending', 'to' => 'picked_up'],
            'occurred_at' => now(),
        ];
    }
}
