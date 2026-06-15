<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    private function pro(): Plan
    {
        return Plan::where('slug', 'pro')->firstOrFail(); // 29 / month
    }

    public function test_active_gateway_falls_back_to_offline_when_unconfigured(): void
    {
        config(['linkforge.billing.gateway' => 'stripe', 'linkforge.billing.stripe.secret' => null]);
        $this->assertSame('offline', app(BillingService::class)->gateway()->key());

        config(['linkforge.billing.stripe.secret' => 'sk_test_x']);
        $this->assertSame('stripe', app(BillingService::class)->gateway()->key());
    }

    public function test_paypal_checkout_creates_pending_payment_and_redirects_to_approval(): void
    {
        Http::fake([
            'api-m.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            'api-m.paypal.com/v2/checkout/orders' => Http::response(['id' => 'ORDER123', 'links' => [['rel' => 'approve', 'href' => 'https://www.paypal.com/checkoutnow?token=ORDER123']]]),
        ]);
        config(['linkforge.billing.gateway' => 'paypal', 'linkforge.billing.paypal.client_id' => 'cid', 'linkforge.billing.paypal.secret' => 'sec', 'linkforge.billing.paypal.mode' => 'live']);

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('billing.subscribe', $this->pro()))
            ->assertRedirect('https://www.paypal.com/checkoutnow?token=ORDER123');

        $this->assertDatabaseHas('payments', ['user_id' => $user->id, 'gateway' => 'paypal', 'gateway_ref' => 'ORDER123', 'status' => 'pending']);
    }

    public function test_paypal_return_captures_and_activates(): void
    {
        Http::fake([
            'api-m.paypal.com/v2/checkout/orders/ORDER123/capture' => Http::response(['status' => 'COMPLETED']),
            'api-m.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
        ]);
        config(['linkforge.billing.gateway' => 'paypal', 'linkforge.billing.paypal.client_id' => 'cid', 'linkforge.billing.paypal.secret' => 'sec', 'linkforge.billing.paypal.mode' => 'live']);

        $user = User::factory()->create();
        $pro = $this->pro();
        app(BillingService::class)->recordPending($user, $pro, 'paypal', 'ORDER123');

        $this->actingAs($user)->get(route('billing.return', ['gateway' => 'paypal']).'?token=ORDER123')->assertRedirect(route('billing.index'));

        $this->assertSame('completed', Payment::where('gateway_ref', 'ORDER123')->first()->status);
        $this->assertSame($pro->id, $user->fresh()->plan_id);
    }

    public function test_coinpayments_checkout_then_signed_ipn_activates(): void
    {
        Http::fake(['www.coinpayments.net/api.php' => Http::response(['error' => 'ok', 'result' => ['txn_id' => 'TXN1', 'checkout_url' => 'https://www.coinpayments.net/index.php?cmd=checkout&id=TXN1']])]);
        config([
            'linkforge.billing.gateway' => 'coinpayments',
            'linkforge.billing.coinpayments.public_key' => 'pub',
            'linkforge.billing.coinpayments.private_key' => 'priv',
            'linkforge.billing.coinpayments.ipn_secret' => 'ipnsecret',
            'linkforge.billing.coinpayments.merchant_id' => 'MID',
        ]);

        $user = User::factory()->create();
        $pro = $this->pro();
        $this->actingAs($user)->post(route('billing.subscribe', $pro))->assertRedirect('https://www.coinpayments.net/index.php?cmd=checkout&id=TXN1');
        $this->assertDatabaseHas('payments', ['gateway' => 'coinpayments', 'gateway_ref' => 'TXN1', 'status' => 'pending']);

        // Signed IPN for a completed payment (status 100).
        $params = ['merchant' => 'MID', 'txn_id' => 'TXN1', 'status' => 100, 'amount1' => '29.00'];
        $body = http_build_query($params);
        $hmac = hash_hmac('sha512', $body, 'ipnsecret');

        $this->call('POST', route('billing.webhook', ['gateway' => 'coinpayments']), $params, [], [], ['HTTP_HMAC' => $hmac, 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'], $body)
            ->assertOk();

        $this->assertSame('completed', Payment::where('gateway_ref', 'TXN1')->first()->status);
        $this->assertSame($pro->id, $user->fresh()->plan_id);
    }

    public function test_coinpayments_ipn_with_bad_signature_is_ignored(): void
    {
        config(['linkforge.billing.coinpayments.ipn_secret' => 'ipnsecret', 'linkforge.billing.coinpayments.merchant_id' => 'MID']);
        $user = User::factory()->create();
        app(BillingService::class)->recordPending($user, $this->pro(), 'coinpayments', 'TXN2');

        $params = ['merchant' => 'MID', 'txn_id' => 'TXN2', 'status' => 100];
        $body = http_build_query($params);

        $this->call('POST', route('billing.webhook', ['gateway' => 'coinpayments']), $params, [], [], ['HTTP_HMAC' => 'wronghmac', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'], $body)
            ->assertOk();

        $this->assertSame('pending', Payment::where('gateway_ref', 'TXN2')->first()->status); // unchanged
    }

    public function test_cryptocom_webhook_refetches_and_activates(): void
    {
        Http::fake(['pay.crypto.com/api/payments/PAY1' => Http::response(['id' => 'PAY1', 'status' => 'succeeded'])]);
        config(['linkforge.billing.cryptocom.secret_key' => 'sk_test_x']);

        $user = User::factory()->create();
        $pro = $this->pro();
        app(BillingService::class)->recordPending($user, $pro, 'cryptocom', 'PAY1');

        $this->postJson(route('billing.webhook', ['gateway' => 'cryptocom']), ['type' => 'invoice.paid', 'data' => ['object' => ['id' => 'PAY1']]])->assertOk();

        $this->assertSame('completed', Payment::where('gateway_ref', 'PAY1')->first()->status);
        $this->assertSame($pro->id, $user->fresh()->plan_id);
    }
}
