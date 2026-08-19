<?php

declare(strict_types=1);

namespace Database\Factories\Integrations\ClientOrders;

use App\Domain\Ordering\Models\Product;
use App\Integrations\ClientOrders\Models\PartnerProductMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerProductMapping>
 */
class PartnerProductMappingFactory extends Factory
{
    protected $model = PartnerProductMapping::class;

    public function definition(): array
    {
        return [
            'partner_key' => 'acme-coldchain',
            'partner_sku' => $this->faker->bothify('ACME-SKU-##??'),
            'product_id' => Product::factory(),
        ];
    }
}
