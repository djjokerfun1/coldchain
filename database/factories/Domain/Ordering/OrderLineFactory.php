<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Ordering;

use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\Models\OrderLine;
use App\Domain\Ordering\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    protected $model = OrderLine::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 50),
        ];
    }
}
