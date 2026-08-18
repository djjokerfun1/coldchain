<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\Contracts;

use App\Integrations\ClientOrders\Exceptions\MalformedPayloadException;
use App\Integrations\ClientOrders\ValueObjects\NormalizedOrder;

interface ClientOrderAdapter
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws MalformedPayloadException if the payload doesn't have this
     *                                   partner's expected shape at all
     */
    public function normalize(array $payload): NormalizedOrder;
}
