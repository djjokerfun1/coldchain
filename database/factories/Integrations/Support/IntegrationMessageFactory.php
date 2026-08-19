<?php

declare(strict_types=1);

namespace Database\Factories\Integrations\Support;

use App\Integrations\Support\Enums\IntegrationMessageStatus;
use App\Integrations\Support\Models\IntegrationMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationMessage>
 */
class IntegrationMessageFactory extends Factory
{
    protected $model = IntegrationMessage::class;

    public function definition(): array
    {
        return [
            'correlation_id' => $this->faker->uuid(),
            'channel' => 'client_order',
            'partner_key' => 'acme-coldchain',
            'status' => IntegrationMessageStatus::Received,
            'raw_payload' => ['order_reference' => $this->faker->bothify('ORD-####')],
            'error' => null,
        ];
    }
}
