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
 * NorthStar Freight's order feed: camelCase keys, dd-MM-yyyy dates, weight
 * in pounds, and its own address key names. Every one of those has to be
 * converted, not just renamed — this is the adapter that actually justifies
 * having an interface at all.
 */
class NorthStarFreightAdapter implements ClientOrderAdapter
{
    private const KG_PER_LB = 0.45359237;

    public function normalize(array $payload): NormalizedOrder
    {
        try {
            return new NormalizedOrder(
                externalReference: $payload['orderNumber'],
                clientEmail: $payload['clientEmail'],
                pickupAddress: $this->address($payload['shipFrom']),
                deliveryAddress: $this->address($payload['shipTo']),
                placedAt: CarbonImmutable::createFromFormat('d-m-Y', $payload['orderDate'])->startOfDay(),
                lines: array_map(
                    fn (array $item): NormalizedOrderLine => new NormalizedOrderLine(
                        partnerSku: $item['partnerProductCode'],
                        quantity: (int) $item['qty'],
                        weightKg: isset($item['weightLbs'])
                            ? round((float) $item['weightLbs'] * self::KG_PER_LB, 3)
                            : null,
                    ),
                    $payload['items'],
                ),
            );
        } catch (Throwable $exception) {
            throw new MalformedPayloadException(
                "Malformed northstar-freight payload: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function address(array $address): Address
    {
        return Address::fromArray([
            'line1' => $address['line1'],
            'line2' => $address['line2'] ?? null,
            'city' => $address['city'],
            'postal_code' => $address['postalCode'],
            'country' => $address['country'],
        ]);
    }
}
