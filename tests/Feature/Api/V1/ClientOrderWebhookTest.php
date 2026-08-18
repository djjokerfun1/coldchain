<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Ordering\Models\Client;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\Models\Product;
use App\Integrations\ClientOrders\Models\PartnerProductMapping;
use App\Integrations\Support\Enums\IntegrationMessageStatus;
use App\Integrations\Support\Models\IntegrationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientOrderWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function acmePayload(array $overrides = []): array
    {
        return array_replace([
            'order_reference' => 'ACME-000123',
            'client_email' => 'ops@brightpath-pharma.test',
            'placed_at' => '2026-08-18T09:15:00+00:00',
            'pickup_address' => [
                'line1' => '1 Cold Storage Way',
                'city' => 'Rotterdam',
                'postal_code' => '3011AA',
                'country' => 'NL',
            ],
            'delivery_address' => [
                'line1' => '99 Pharma Ave',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country' => 'DE',
            ],
            'lines' => [
                ['product_code' => 'ACME-SKU-1', 'quantity' => 4, 'weight_kg' => 12.5],
            ],
        ], $overrides);
    }

    private function northStarPayload(array $overrides = []): array
    {
        return array_replace([
            'orderNumber' => 'NSF-98213',
            'clientEmail' => 'ops@brightpath-pharma.test',
            'orderDate' => '18-08-2026',
            'shipFrom' => [
                'line1' => '400 Freight Yard Rd',
                'city' => 'Chicago',
                'postalCode' => '60607',
                'country' => 'US',
            ],
            'shipTo' => [
                'line1' => '12 Distribution Blvd',
                'city' => 'Columbus',
                'postalCode' => '43004',
                'country' => 'US',
            ],
            'items' => [
                ['partnerProductCode' => 'NSF-PC-1', 'qty' => 2, 'weightLbs' => 27.56],
            ],
        ], $overrides);
    }

    private function postSigned(string $partner, string $secret, array $payload): \Illuminate\Testing\TestResponse
    {
        $signature = hash_hmac('sha256', (string) json_encode($payload), $secret);

        return $this->postJson(
            "/api/v1/webhooks/client-orders/{$partner}",
            $payload,
            ['X-Signature' => $signature],
        );
    }

    public function test_it_rejects_a_request_with_no_signature(): void
    {
        $this->postJson('/api/v1/webhooks/client-orders/acme-coldchain', $this->acmePayload())
            ->assertUnauthorized();
    }

    public function test_it_rejects_a_request_with_an_invalid_signature(): void
    {
        $this->postJson(
            '/api/v1/webhooks/client-orders/acme-coldchain',
            $this->acmePayload(),
            ['X-Signature' => 'not-the-real-signature'],
        )->assertUnauthorized();
    }

    public function test_it_rejects_an_unknown_partner(): void
    {
        $this->postSigned('does-not-exist', 'whatever', $this->acmePayload())
            ->assertNotFound();
    }

    public function test_it_ingests_an_acme_coldchain_order(): void
    {
        Client::factory()->create(['email' => 'ops@brightpath-pharma.test']);
        $product = Product::factory()->create();
        PartnerProductMapping::factory()->create([
            'partner_key' => 'acme-coldchain',
            'partner_sku' => 'ACME-SKU-1',
            'product_id' => $product->id,
        ]);

        $this->postSigned('acme-coldchain', config('client_order_partners.acme-coldchain.secret'), $this->acmePayload())
            ->assertAccepted()
            ->assertJsonPath('data.lines.0.quantity', 4)
            ->assertJsonPath('data.lines.0.weight_kg', 12.5);

        $order = Order::sole();
        $this->assertSame('acme-coldchain', $order->source_partner_key);
        $this->assertSame('ACME-000123', $order->external_reference);
        $this->assertSame(IntegrationMessageStatus::Processed, IntegrationMessage::sole()->status);
    }

    public function test_it_converts_north_star_dates_and_weight_into_our_own_units(): void
    {
        Client::factory()->create(['email' => 'ops@brightpath-pharma.test']);
        $product = Product::factory()->create();
        PartnerProductMapping::factory()->create([
            'partner_key' => 'northstar-freight',
            'partner_sku' => 'NSF-PC-1',
            'product_id' => $product->id,
        ]);

        $this->postSigned('northstar-freight', config('client_order_partners.northstar-freight.secret'), $this->northStarPayload())
            ->assertAccepted();

        $order = Order::sole();
        $this->assertSame('2026-08-18', $order->placed_at->toDateString());
        $this->assertEqualsWithDelta(12.501, $order->lines->sole()->weight_kg, 0.001);
    }

    public function test_a_replayed_webhook_does_not_create_a_second_order(): void
    {
        Client::factory()->create(['email' => 'ops@brightpath-pharma.test']);
        $product = Product::factory()->create();
        PartnerProductMapping::factory()->create([
            'partner_key' => 'acme-coldchain',
            'partner_sku' => 'ACME-SKU-1',
            'product_id' => $product->id,
        ]);

        $secret = config('client_order_partners.acme-coldchain.secret');
        $payload = $this->acmePayload();

        $this->postSigned('acme-coldchain', $secret, $payload)->assertAccepted();
        $this->postSigned('acme-coldchain', $secret, $payload)->assertAccepted();

        $this->assertSame(1, Order::count());
        $this->assertSame(
            [IntegrationMessageStatus::Processed, IntegrationMessageStatus::Duplicate],
            IntegrationMessage::orderBy('id')->pluck('status')->all(),
        );
    }

    public function test_an_unregistered_client_email_is_rejected(): void
    {
        Product::factory()->create();

        $this->postSigned(
            'acme-coldchain',
            config('client_order_partners.acme-coldchain.secret'),
            $this->acmePayload(),
        )->assertUnprocessable();

        $this->assertSame(0, Order::count());
        $this->assertSame(IntegrationMessageStatus::Failed, IntegrationMessage::sole()->status);
    }

    public function test_an_unmapped_partner_product_code_is_rejected(): void
    {
        Client::factory()->create(['email' => 'ops@brightpath-pharma.test']);

        $this->postSigned(
            'acme-coldchain',
            config('client_order_partners.acme-coldchain.secret'),
            $this->acmePayload(),
        )->assertUnprocessable();

        $this->assertSame(0, Order::count());
    }

    public function test_a_malformed_payload_is_rejected_without_a_server_error(): void
    {
        Client::factory()->create(['email' => 'ops@brightpath-pharma.test']);

        $payload = $this->acmePayload();
        unset($payload['lines']);

        $this->postSigned('acme-coldchain', config('client_order_partners.acme-coldchain.secret'), $payload)
            ->assertUnprocessable();

        $this->assertSame(IntegrationMessageStatus::Failed, IntegrationMessage::sole()->status);
    }
}
