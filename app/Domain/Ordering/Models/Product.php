<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Models;

use App\Domain\ColdChain\ValueObjects\TemperatureRange;
use App\Domain\Ordering\Enums\StorageClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\Ordering\ProductFactory> */
    use HasFactory;

    protected $fillable = ['sku', 'name', 'storage_class'];

    protected function casts(): array
    {
        return [
            'storage_class' => StorageClass::class,
        ];
    }

    public function temperatureRange(): ?TemperatureRange
    {
        return $this->storage_class->temperatureRange();
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }
}
