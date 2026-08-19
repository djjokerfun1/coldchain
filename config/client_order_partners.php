<?php

declare(strict_types=1);

use App\Integrations\ClientOrders\Adapters\AcmeColdChainAdapter;
use App\Integrations\ClientOrders\Adapters\NorthStarFreightAdapter;

return [

    /*
    |--------------------------------------------------------------------------
    | Client order partners
    |--------------------------------------------------------------------------
    |
    | Each partner posts to POST /api/v1/webhooks/client-orders/{partner},
    | signing the raw request body with HMAC-SHA256 under their own secret.
    | The adapter is what turns their payload shape into a NormalizedOrder;
    | see App\Integrations\ClientOrders\Contracts\ClientOrderAdapter.
    |
    */

    'acme-coldchain' => [
        'secret' => env('PARTNER_ACME_COLDCHAIN_SECRET'),
        'adapter' => AcmeColdChainAdapter::class,
    ],

    'northstar-freight' => [
        'secret' => env('PARTNER_NORTHSTAR_FREIGHT_SECRET'),
        'adapter' => NorthStarFreightAdapter::class,
    ],

];
