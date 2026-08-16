<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Ordering\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'client' => new ClientResource($this->whenLoaded('client')),
            'lines' => OrderLineResource::collection($this->whenLoaded('lines')),
            'pickup_address' => $this->pickup_address->toArray(),
            'delivery_address' => $this->delivery_address->toArray(),
            'placed_at' => $this->placed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
