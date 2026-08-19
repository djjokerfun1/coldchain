<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\Exceptions;

use RuntimeException;

/**
 * Thrown by an adapter when a payload doesn't have the shape it expects
 * (a missing required key, a value that won't parse) — distinct from a
 * payload that parses fine but references a client or product we don't
 * recognise, which IngestClientOrder handles itself after normalisation.
 */
class MalformedPayloadException extends RuntimeException {}
