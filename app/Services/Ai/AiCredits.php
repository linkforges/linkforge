<?php

namespace App\Services\Ai;

use App\Models\User;

/**
 * Meters the per-user AI credit allowance (users.ai_credits). Credits are
 * granted per plan in PlanSeeder and topped up on plan activation. Charges are
 * atomic so concurrent requests can't overspend the balance.
 */
class AiCredits
{
    public function balance(User $user): int
    {
        return (int) $user->ai_credits;
    }

    public function has(User $user, int $cost = 1): bool
    {
        return $this->balance($user) >= $cost;
    }

    /**
     * Deduct credits atomically. Returns false (charging nothing) when the
     * balance is insufficient, so callers can gate the AI call on the result.
     */
    public function charge(User $user, int $cost = 1): bool
    {
        $affected = User::whereKey($user->getKey())
            ->where('ai_credits', '>=', $cost)
            ->decrement('ai_credits', $cost);

        if ($affected) {
            $user->ai_credits = max(0, $this->balance($user) - $cost);
        }

        return (bool) $affected;
    }

    /** Refund credits, e.g. when an AI call fails after charging. */
    public function refund(User $user, int $cost = 1): void
    {
        User::whereKey($user->getKey())->increment('ai_credits', $cost);
        $user->ai_credits = $this->balance($user) + $cost;
    }
}
