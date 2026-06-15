<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Crypto.com Pay. Checkout creates a payment (amount in minor units) and records
 * a pending payment keyed by the payment id; the buyer pays on the hosted
 * payment_url. Confirmation is async via webhook — on receipt we re-fetch the
 * payment from the API (authoritative, so a forged webhook can't mark it paid).
 */
class CryptoComGateway extends AbstractGateway
{
    private const API = 'https://pay.crypto.com/api';

    public function key(): string
    {
        return 'cryptocom';
    }

    public function label(): string
    {
        return 'Crypto.com Pay';
    }

    public function configured(): bool
    {
        return ! empty(config('linkforge.billing.cryptocom.secret_key'));
    }

    public function checkout(User $user, Plan $plan): ?string
    {
        $res = Http::withBasicAuth(config('linkforge.billing.cryptocom.secret_key'), '')
            ->post(self::API.'/payments', [
                'amount' => (int) round((float) $plan->price * 100),
                'currency' => strtoupper($plan->currency),
                'description' => config('linkforge.name').' '.$plan->name,
                'order_id' => 'plan-'.$plan->id.'-user-'.$user->id.'-'.now()->timestamp,
                'return_url' => $this->returnUrl(),
                'cancel_url' => $this->cancelUrl(),
                'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id],
            ]);

        $id = $res->json('id');
        $url = $res->json('payment_url');
        if (! $id || ! $url) {
            return null;
        }
        $this->billing->recordPending($user, $plan, 'cryptocom', $id);

        return $url;
    }

    public function handleWebhook(Request $request): void
    {
        if (! $this->configured()) {
            return;
        }

        // The payment id may sit at a couple of places depending on the event envelope.
        $object = $request->input('data.object', $request->input('data', []));
        $id = $object['id'] ?? $request->input('id');
        if (! $id) {
            return;
        }

        // Re-fetch the payment from Crypto.com — authoritative, so a forged webhook can't fake a paid status.
        $payment = Http::withBasicAuth(config('linkforge.billing.cryptocom.secret_key'), '')->get(self::API.'/payments/'.$id)->json();
        $status = strtolower((string) ($payment['status'] ?? ''));

        if (in_array($status, ['succeeded', 'captured', 'paid'], true)) {
            $this->billing->confirmPending('cryptocom', (string) $id);
        }
    }
}
