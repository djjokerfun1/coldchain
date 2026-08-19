<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\ValueObjects;

use App\Domain\Ordering\ValueObjects\Address;
use Carbon\CarbonImmutable;

/**
 * The common shape every partner payload gets reduced to, regardless of
 * how differently the two of them spell the same fields. IngestClientOrder
 * only ever deals with this, never with a partner's raw array.
 */
final readonly class NormalizedOrder
{
    /**
     * @param  list<NormalizedOrderLine>  $lines
     */
    public function __construct(
        public string $externalReference,
        public string $clientEmail,
        public Address $pickupAddress,
        public Address $deliveryAddress,
        public CarbonImmutable $placedAt,
        public array $lines,
    ) {}
}
