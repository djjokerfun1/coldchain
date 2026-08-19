<?php

declare(strict_types=1);

namespace App\Integrations\Support\Models;

use App\Integrations\Support\Enums\IntegrationMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A record of every inbound message from an external channel (partner order
 * webhooks now, EDI later), independent of whether it was ever successfully
 * turned into a domain record. This is what a "why didn't the order show
 * up" investigation starts from, and what a future replay command reads.
 */
class IntegrationMessage extends Model
{
    /** @use HasFactory<\Database\Factories\Integrations\Support\IntegrationMessageFactory> */
    use HasFactory;

    protected $fillable = ['correlation_id', 'channel', 'partner_key', 'status', 'raw_payload', 'error'];

    protected function casts(): array
    {
        return [
            'status' => IntegrationMessageStatus::class,
            'raw_payload' => 'array',
        ];
    }

    public function markProcessed(): void
    {
        $this->update(['status' => IntegrationMessageStatus::Processed, 'error' => null]);
    }

    public function markDuplicate(): void
    {
        $this->update(['status' => IntegrationMessageStatus::Duplicate, 'error' => null]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => IntegrationMessageStatus::Failed, 'error' => $error]);
    }
}
