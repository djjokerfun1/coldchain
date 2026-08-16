<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Ordering\Enums\StorageClass;
use App\Domain\Ordering\Models\OrderLine;
use App\Domain\Ordering\Models\Product;

class ProductApiTest extends ApiTestCase
{
    public function test_it_lists_products(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_filters_products_by_storage_class(): void
    {
        Product::factory()->refrigerated()->create();
        Product::factory()->frozen()->create();

        $response = $this->getJson('/api/v1/products?filter[storage_class]=frozen');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.storage_class', 'frozen');
    }

    public function test_a_refrigerated_product_exposes_its_gdp_range(): void
    {
        $product = Product::factory()->refrigerated()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        // JSON has no float/int distinction: 2.0 round-trips as the integer 2.
        $response->assertOk()
            ->assertJsonPath('data.temperature_range.min_celsius', 2)
            ->assertJsonPath('data.temperature_range.max_celsius', 8);
    }

    public function test_it_creates_a_product(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SKU-001',
            'name' => 'Insulin vials',
            'storage_class' => StorageClass::Refrigerated->value,
        ]);

        $response->assertCreated()->assertJsonPath('data.sku', 'SKU-001');
    }

    public function test_it_rejects_an_invalid_storage_class(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SKU-002',
            'name' => 'Widget',
            'storage_class' => 'lukewarm',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('storage_class');
    }

    public function test_it_rejects_a_duplicate_sku(): void
    {
        Product::factory()->create(['sku' => 'SKU-001']);

        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SKU-001',
            'name' => 'Duplicate',
            'storage_class' => StorageClass::Ambient->value,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('sku');
    }

    public function test_it_updates_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->patchJson("/api/v1/products/{$product->id}", ['name' => 'New Name']);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_it_refuses_to_delete_a_product_referenced_by_order_lines(): void
    {
        $product = Product::factory()->create();
        OrderLine::factory()->create(['product_id' => $product->id]);

        $this->deleteJson("/api/v1/products/{$product->id}")->assertConflict();
    }
}
