<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\ValueObjects;

final readonly class NormalizedOrderLine
{
    public function __construct(
        public string $partnerSku,
        public int $quantity,
        public ?float $weightKg,
    ) {}
}
