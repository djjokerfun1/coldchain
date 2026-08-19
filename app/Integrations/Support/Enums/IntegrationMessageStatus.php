<?php

declare(strict_types=1);

namespace App\Integrations\Support\Enums;

enum IntegrationMessageStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Duplicate = 'duplicate';
    case Failed = 'failed';
}
