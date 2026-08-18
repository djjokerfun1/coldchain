<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\Adapters;

use App\Domain\Ordering\ValueObjects\Address;
use App\Integrations\ClientOrders\Contracts\ClientOrderAdapter;
use App\Integrations\ClientOrders\Exceptions\MalformedPayloadException;
use App\Integrations\ClientOrders\ValueObjects\NormalizedOrder;
use App\Integrations\ClientOrders\ValueObjects\NormalizedOrderLine;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * AcmeColdChain's order feed: snake_case keys, ISO 8601 timestamps, weight
 * already in kilograms. The closest of the two partner formats to our own
 * internal shape, which is exactly why it's not a safe template for the
 * next adapter — nothing here forces the interface to earn its keep.
 */
class AcmeColdChainAdapter implements ClientOrderAdapter
{
    public function normalize(array $payload): NormalizedOrder
    {
        try {
            return new NormalizedOrder(
                externalReference: $payload['order_reference'],
                clientEmail: $payload['client_email'],
                pickupAddress: Address::fromArray($payload['pickup_address']),
                deliveryAddress: Address::fromArray($payload['delivery_address']),
                placedAt: CarbonImmutable::parse($payload['placed_at']),
                lines: array_map(
                    fn (array $line): NormalizedOrderLine => new NormalizedOrderLine(
                        partnerSku: $line['product_code'],
                        quantity: (int) $line['quantity'],
                        weightKg: isset($line['weight_kg']) ? (float) $line['weight_kg'] : null,
                    ),
                    $payload['lines'],
                ),
            );
        } catch (Throwable $exception) {
            throw new MalformedPayloadException(
                "Malformed acme-coldchain payload: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }
}
