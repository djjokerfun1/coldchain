<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Ordering;

use App\Domain\Ordering\Enums\StorageClass;
use App\Domain\Ordering\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('??-####')),
            'name' => $this->faker->words(3, true),
            'storage_class' => $this->faker->randomElement(StorageClass::cases()),
        ];
    }

    public function refrigerated(): static
    {
        return $this->state(['storage_class' => StorageClass::Refrigerated]);
    }

    public function frozen(): static
    {
        return $this->state(['storage_class' => StorageClass::Frozen]);
    }
}
