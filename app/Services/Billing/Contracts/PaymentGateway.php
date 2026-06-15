<?php

namespace App\Services\Billing\Contracts;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Stable machine key, e.g. "stripe". */
    public function key(): string;

    /** Human label for the admin UI. */
    public function label(): string;

    /** Whether the required credentials are present. */
    public function configured(): bool;

    /**
     * Begin checkout for a plan. Returns a hosted-checkout redirect URL, or
     * null when the change was applied immediately (offline / free).
     */
    public function checkout(User $user, Plan $plan): ?string;

    /**
     * Handle the customer returning from the hosted page. Returns the activated
     * Plan when confirmed synchronously, or null when confirmation is async.
     */
    public function handleReturn(Request $request): ?Plan;

    /** Handle an asynchronous webhook/IPN. Verifies authenticity, then confirms. */
    public function handleWebhook(Request $request): void;
}
