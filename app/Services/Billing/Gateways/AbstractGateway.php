<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Services\Billing\BillingService;
use App\Services\Billing\Contracts\PaymentGateway;
use Illuminate\Http\Request;

abstract class AbstractGateway implements PaymentGateway
{
    public function __construct(protected BillingService $billing) {}

    public function handleReturn(Request $request): ?Plan
    {
        return null;
    }

    public function handleWebhook(Request $request): void {}

    /** Where the customer returns to after this gateway's hosted page. */
    protected function returnUrl(): string
    {
        return route('billing.return', $this->key());
    }

    protected function cancelUrl(): string
    {
        return route('billing.index');
    }

    protected function webhookUrl(): string
    {
        return route('billing.webhook', $this->key());
    }
}
