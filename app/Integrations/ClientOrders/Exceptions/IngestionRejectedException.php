<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\Exceptions;

use RuntimeException;

/**
 * A payload that parsed fine but references a client or product we don't
 * recognise — distinct from MalformedPayloadException, which means the
 * shape itself didn't match what the adapter expects.
 */
class IngestionRejectedException extends RuntimeException {}
