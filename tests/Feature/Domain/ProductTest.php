<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\Ordering\Enums\StorageClass;
use App\Domain\Ordering\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_class_casts_to_the_enum(): void
    {
        $product = Product::factory()->refrigerated()->create();

        $this->assertSame(StorageClass::Refrigerated, $product->storage_class);
    }

    public function test_temperature_range_is_derived_from_storage_class(): void
    {
        $ambient = Product::factory()->create(['storage_class' => StorageClass::Ambient]);
        $frozen = Product::factory()->frozen()->create();

        $this->assertNull($ambient->temperatureRange());
        $this->assertNotNull($frozen->temperatureRange());
    }
}
