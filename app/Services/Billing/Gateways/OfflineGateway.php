<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\User;

/**
 * Applies plan changes immediately. Used for free plans, manual/bank-transfer
 * billing, and as the default when no payment provider is configured.
 */
class OfflineGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'offline';
    }

    public function label(): string
    {
        return 'Offline / manual';
    }

    public function configured(): bool
    {
        return true;
    }

    public function checkout(User $user, Plan $plan): ?string
    {
        $this->billing->activate($user, $plan, 'offline');

        return null;
    }
}
