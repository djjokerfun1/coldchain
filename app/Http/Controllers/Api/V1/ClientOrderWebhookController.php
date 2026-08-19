<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClientOrderWebhookRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Integrations\ClientOrders\Actions\IngestClientOrder;
use App\Integrations\ClientOrders\Exceptions\IngestionRejectedException;
use App\Integrations\ClientOrders\Exceptions\MalformedPayloadException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ClientOrderWebhookController extends Controller
{
    public function store(
        StoreClientOrderWebhookRequest $request,
        string $partner,
        IngestClientOrder $ingest,
    ): JsonResponse {
        /** @var array{secret: string, adapter: class-string<\App\Integrations\ClientOrders\Contracts\ClientOrderAdapter>} $config */
        $config = config("client_order_partners.{$partner}");
        $adapter = app($config['adapter']);

        try {
            $order = $ingest->handle($partner, $adapter, $request->all());
        } catch (MalformedPayloadException|IngestionRejectedException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return OrderResource::make($order->load('client', 'lines.product'))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
