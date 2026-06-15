<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * CoinPayments (classic API). create_transaction is HMAC-SHA512 signed with the
 * private key; the buyer pays on the returned checkout_url. Confirmation is async
 * via the IPN webhook, verified with the IPN secret (status >= 100 or == 2 = paid).
 * The merchant's receive coin (currency2) is operator-configurable.
 */
class CoinPaymentsGateway extends AbstractGateway
{
    private const API = 'https://www.coinpayments.net/api.php';

    public function key(): string
    {
        return 'coinpayments';
    }

    public function label(): string
    {
        return 'CoinPayments (crypto)';
    }

    public function configured(): bool
    {
        return ! empty(config('linkforge.billing.coinpayments.public_key'))
            && ! empty(config('linkforge.billing.coinpayments.private_key'));
    }

    public function checkout(User $user, Plan $plan): ?string
    {
        $params = [
            'version' => 1,
            'key' => config('linkforge.billing.coinpayments.public_key'),
            'format' => 'json',
            'cmd' => 'create_transaction',
            'amount' => number_format((float) $plan->price, 8, '.', ''),
            'currency1' => strtoupper($plan->currency),
            'currency2' => strtoupper(config('linkforge.billing.coinpayments.receive_currency') ?: 'BTC'),
            'buyer_email' => $user->email,
            'item_name' => config('linkforge.name').' '.$plan->name,
            'ipn_url' => $this->webhookUrl(),
            'success_url' => $this->returnUrl(),
            'cancel_url' => $this->cancelUrl(),
        ];

        // The HMAC must cover exactly the bytes we send, so build the body ourselves.
        $body = http_build_query($params);
        $hmac = hash_hmac('sha512', $body, (string) config('linkforge.billing.coinpayments.private_key'));

        $res = Http::withBody($body, 'application/x-www-form-urlencoded')->withHeaders(['HMAC' => $hmac])->post(self::API);

        if ($res->json('error') !== 'ok') {
            return null;
        }

        $result = $res->json('result');
        if (empty($result['txn_id']) || empty($result['checkout_url'])) {
            return null;
        }
        $this->billing->recordPending($user, $plan, 'coinpayments', $result['txn_id']);

        return $result['checkout_url'];
    }

    public function handleWebhook(Request $request): void
    {
        $secret = config('linkforge.billing.coinpayments.ipn_secret');
        $merchant = config('linkforge.billing.coinpayments.merchant_id');
        $header = $request->header('HMAC');
        if (! $secret || ! $header) {
            return;
        }

        $calc = hash_hmac('sha512', $request->getContent(), (string) $secret);
        if (! hash_equals($calc, $header)) {
            return;
        }
        if ($merchant && $request->input('merchant') !== $merchant) {
            return;
        }

        $status = (int) $request->input('status');
        if (($status >= 100 || $status === 2) && $request->filled('txn_id')) {
            $this->billing->confirmPending('coinpayments', (string) $request->input('txn_id'));
        }
    }
}
