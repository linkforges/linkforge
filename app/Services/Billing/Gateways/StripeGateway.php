<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Stripe Checkout (subscription mode). Checkout records a pending payment keyed
 * by the Checkout Session id; activation is confirmed when the customer returns
 * (we re-fetch the session — authoritative) and, optionally, by a signed webhook.
 */
class StripeGateway extends AbstractGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function configured(): bool
    {
        return ! empty(config('linkforge.billing.stripe.secret'));
    }

    public function checkout(User $user, Plan $plan): ?string
    {
        $response = Http::asForm()->withToken(config('linkforge.billing.stripe.secret'))->post(self::API.'/checkout/sessions', [
            'mode' => 'subscription',
            'success_url' => $this->returnUrl().'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->cancelUrl(),
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($plan->currency),
            'line_items[0][price_data][unit_amount]' => (int) round((float) $plan->price * 100),
            'line_items[0][price_data][recurring][interval]' => $plan->interval === 'year' ? 'year' : 'month',
            'line_items[0][price_data][product_data][name]' => config('linkforge.name').' '.$plan->name,
            'metadata[plan_id]' => (string) $plan->id,
        ]);

        $id = $response->json('id');
        $url = $response->json('url');
        if ($id && $url) {
            $this->billing->recordPending($user, $plan, 'stripe', $id);
        }

        return $url;
    }

    public function handleReturn(Request $request): ?Plan
    {
        $sessionId = (string) $request->query('session_id');
        if ($sessionId === '' || ! $this->configured()) {
            return null;
        }

        $session = Http::withToken(config('linkforge.billing.stripe.secret'))->get(self::API.'/checkout/sessions/'.$sessionId)->json();
        if (($session['payment_status'] ?? null) !== 'paid') {
            return null;
        }

        return $this->billing->confirmPending('stripe', $sessionId)?->plan;
    }

    public function handleWebhook(Request $request): void
    {
        $secret = config('linkforge.billing.stripe.webhook_secret');
        if (! $secret || ! $this->verifySignature($request, $secret)) {
            return; // unsigned/unverifiable webhooks are ignored
        }

        $event = $request->json()->all();
        if (($event['type'] ?? null) === 'checkout.session.completed') {
            $sessionId = $event['data']['object']['id'] ?? null;
            if ($sessionId) {
                $this->billing->confirmPending('stripe', $sessionId);
            }
        }
    }

    /** Verify Stripe's `Stripe-Signature` header (HMAC-SHA256 of "timestamp.payload"). */
    private function verifySignature(Request $request, string $secret): bool
    {
        $header = $request->header('Stripe-Signature', '');
        parse_str(str_replace(',', '&', $header), $parts);
        $t = $parts['t'] ?? null;
        $v1 = $parts['v1'] ?? null;
        if (! $t || ! $v1) {
            return false;
        }
        $expected = hash_hmac('sha256', $t.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $v1);
    }
}
