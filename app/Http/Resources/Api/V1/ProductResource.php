<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Ordering\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $range = $this->temperatureRange();

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'storage_class' => $this->storage_class->value,
            'temperature_range' => $range === null ? null : [
                'min_celsius' => $range->min->value,
                'max_celsius' => $range->max->value,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
