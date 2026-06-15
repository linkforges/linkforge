<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * PayPal Orders API v2 (one-time capture). Checkout creates an order and records
 * a pending payment keyed by the order id; on return we capture the order
 * (authoritative) and confirm. Honours sandbox vs. live via the configured mode.
 */
class PayPalGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function configured(): bool
    {
        return ! empty(config('linkforge.billing.paypal.client_id')) && ! empty(config('linkforge.billing.paypal.secret'));
    }

    private function base(): string
    {
        return config('linkforge.billing.paypal.mode') === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function token(): ?string
    {
        $res = Http::asForm()
            ->withBasicAuth(config('linkforge.billing.paypal.client_id'), config('linkforge.billing.paypal.secret'))
            ->post($this->base().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        return $res->json('access_token');
    }

    public function checkout(User $user, Plan $plan): ?string
    {
        $token = $this->token();
        if (! $token) {
            return null;
        }

        $res = Http::withToken($token)->post($this->base().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => ['currency_code' => strtoupper($plan->currency), 'value' => number_format((float) $plan->price, 2, '.', '')],
                'description' => config('linkforge.name').' '.$plan->name,
                'custom_id' => (string) $user->id,
            ]],
            'application_context' => [
                'brand_name' => config('linkforge.name'),
                'user_action' => 'PAY_NOW',
                'return_url' => $this->returnUrl(),
                'cancel_url' => $this->cancelUrl(),
            ],
        ]);

        $orderId = $res->json('id');
        if (! $orderId) {
            return null;
        }
        $this->billing->recordPending($user, $plan, 'paypal', $orderId);

        foreach ((array) $res->json('links', []) as $link) {
            if (($link['rel'] ?? null) === 'approve') {
                return $link['href'];
            }
        }

        return null;
    }

    public function handleReturn(Request $request): ?Plan
    {
        $orderId = (string) $request->query('token'); // PayPal returns ?token=<orderID>
        $token = $orderId !== '' ? $this->token() : null;
        if (! $token) {
            return null;
        }

        $capture = Http::withToken($token)->post($this->base()."/v2/checkout/orders/{$orderId}/capture", []);
        if ($capture->json('status') !== 'COMPLETED') {
            return null;
        }

        return $this->billing->confirmPending('paypal', $orderId)?->plan;
    }
}
