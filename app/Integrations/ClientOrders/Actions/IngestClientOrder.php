<?php

declare(strict_types=1);

namespace App\Integrations\ClientOrders\Actions;

use App\Domain\Ordering\Enums\OrderStatus;
use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Integrations\ClientOrders\Contracts\ClientOrderAdapter;
use App\Integrations\ClientOrders\Exceptions\IngestionRejectedException;
use App\Integrations\ClientOrders\Exceptions\MalformedPayloadException;
use App\Integrations\ClientOrders\Models\PartnerProductMapping;
use App\Integrations\ClientOrders\ValueObjects\NormalizedOrder;
use App\Integrations\Support\Models\IntegrationMessage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Every payload gets an IntegrationMessage row before anything else happens
 * to it — a partner payload we failed to make sense of is exactly the one
 * that most needs to survive somewhere for investigation, not disappear
 * into a 422 response and a log line nobody greps for in time.
 */
class IngestClientOrder
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $partnerKey, ClientOrderAdapter $adapter, array $payload): Order
    {
        $message = IntegrationMessage::create([
            'correlation_id' => (string) Str::uuid(),
            'channel' => 'client_order',
            'partner_key' => $partnerKey,
            'raw_payload' => $payload,
        ]);

        try {
            $normalized = $adapter->normalize($payload);

            $existing = Order::where('source_partner_key', $partnerKey)
                ->where('external_reference', $normalized->externalReference)
                ->first();

            if ($existing !== null) {
                $message->markDuplicate();

                return $existing;
            }

            // The same catch-outside-the-transaction shape as telemetry
            // ingestion (ADR 0005): two retries of the same webhook racing
            // past the existence check above both attempt the insert, and
            // the partial unique index on (source_partner_key,
            // external_reference) is what actually decides it.
            try {
                $order = $this->createOrder($partnerKey, $normalized);
            } catch (UniqueConstraintViolationException) {
                $message->markDuplicate();

                return Order::where('source_partner_key', $partnerKey)
                    ->where('external_reference', $normalized->externalReference)
                    ->firstOrFail();
            }

            $message->markProcessed();

            return $order;
        } catch (MalformedPayloadException|IngestionRejectedException $exception) {
            $message->markFailed($exception->getMessage());

            throw $exception;
        }
    }

    private function createOrder(string $partnerKey, NormalizedOrder $normalized): Order
    {
        $client = Client::where('email', $normalized->clientEmail)->first();

        if ($client === null) {
            throw new IngestionRejectedException("No client is registered for {$normalized->clientEmail}.");
        }

        return DB::transaction(function () use ($partnerKey, $normalized, $client): Order {
            $order = Order::create([
                'client_id' => $client->id,
                'source_partner_key' => $partnerKey,
                'external_reference' => $normalized->externalReference,
                'reference' => 'ORD-'.strtoupper(Str::random(6)),
                'status' => OrderStatus::Placed,
                'pickup_address' => $normalized->pickupAddress,
                'delivery_address' => $normalized->deliveryAddress,
                'placed_at' => $normalized->placedAt,
            ]);

            foreach ($normalized->lines as $line) {
                $mapping = PartnerProductMapping::where('partner_key', $partnerKey)
                    ->where('partner_sku', $line->partnerSku)
                    ->first();

                if ($mapping === null) {
                    throw new IngestionRejectedException(
                        "No product mapping for {$partnerKey} code \"{$line->partnerSku}\".",
                    );
                }

                $order->lines()->create([
                    'product_id' => $mapping->product_id,
                    'quantity' => $line->quantity,
                    'weight_kg' => $line->weightKg,
                ]);
            }

            return $order;
        });
    }
}
