<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Ordering\Models\OrderLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderLine
 */
class OrderLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'weight_kg' => $this->weight_kg,
        ];
    }
}
