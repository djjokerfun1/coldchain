<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Shipping\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TrackingEvent
 */
class TrackingEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'external_event_id' => $this->external_event_id,
            'position' => $this->position === null ? null : [
                'latitude' => $this->position->latitude,
                'longitude' => $this->position->longitude,
            ],
            'recorded_at' => $this->recorded_at,
        ];
    }
}
