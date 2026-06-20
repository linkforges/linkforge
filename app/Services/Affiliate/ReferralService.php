<?php

namespace App\Services\Affiliate;

use App\Models\Payment;
use App\Models\PayoutRequest;
use App\Models\ReferralCommission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Affiliate / referral program: referral codes, click + signup attribution,
 * commission creation on referred conversions, and payout requests. All driven
 * by the admin "affiliate_*" settings; a no-op when the program is disabled.
 */
class ReferralService
{
    public function enabled(): bool
    {
        return Setting::get('affiliate_enabled', '0') === '1';
    }

    /**
     * @return array{type:string, value:float, cookie_days:int, min_payout:float, currency:string}
     */
    public function settings(): array
    {
        return [
            'type' => (string) Setting::get('affiliate_commission_type', 'percent'), // percent|fixed
            'value' => (float) Setting::get('affiliate_commission_value', 20),
            'cookie_days' => (int) Setting::get('affiliate_cookie_days', 30),
            'min_payout' => (float) Setting::get('affiliate_min_payout', 50),
            'currency' => (string) Setting::get('currency', 'USD'),
        ];
    }

    /** Ensure the user has a referral code and return it. */
    public function codeFor(User $user): string
    {
        if (! $user->referral_code) {
            do {
                $code = Str::lower(Str::random(8));
            } while (User::where('referral_code', $code)->exists());
            $user->forceFill(['referral_code' => $code])->save();
        }

        return $user->referral_code;
    }

    public function referralUrl(User $user): string
    {
        return url('/ref/'.$this->codeFor($user));
    }

    /** Record a click on a referral code; returns the referrer (or null). */
    public function trackClick(string $code): ?User
    {
        $referrer = User::where('referral_code', $code)->first();
        $referrer?->increment('referral_clicks');

        return $referrer;
    }

    /** Attribute a freshly registered user to a referrer from a code (cookie value). */
    public function attributeSignup(User $newUser, ?string $code): void
    {
        if (! $this->enabled() || ! $code) {
            return;
        }

        $referrer = User::where('referral_code', $code)->first();
        if (! $referrer || (int) $referrer->id === (int) $newUser->id) {
            return; // unknown code or self-referral
        }

        $newUser->forceFill(['referred_by' => $referrer->id])->save();
    }

    /**
     * Create the affiliate commission for a completed payment when the payer was
     * referred and the program is on. Idempotent on payment_id.
     */
    public function commissionForPayment(Payment $payment): ?ReferralCommission
    {
        if (! $this->enabled() || (float) $payment->amount <= 0) {
            return null;
        }

        $payer = $payment->user ?: User::find($payment->user_id);
        if (! $payer || ! $payer->referred_by) {
            return null;
        }

        if (ReferralCommission::where('payment_id', $payment->id)->exists()) {
            return null; // already recorded for this conversion
        }

        $s = $this->settings();
        $amount = $s['type'] === 'fixed'
            ? $s['value']
            : round((float) $payment->amount * $s['value'] / 100, 2);
        if ($amount <= 0) {
            return null;
        }

        return ReferralCommission::create([
            'referrer_id' => $payer->referred_by,
            'referred_user_id' => $payer->id,
            'payment_id' => $payment->id,
            'amount' => $amount,
            'currency' => $payment->currency ?: $s['currency'],
            'status' => 'pending',
            'note' => 'Commission on payment by '.$payer->email,
        ]);
    }

    /** Sum of a user's commissions across the given statuses. */
    public function balance(User $user, array $statuses): float
    {
        return (float) $user->commissions()->whereIn('status', $statuses)->sum('amount');
    }

    /** Approved, not-yet-requested commissions available for payout. */
    public function payableBalance(User $user): float
    {
        return (float) $user->commissions()
            ->where('status', 'approved')
            ->whereNull('payout_request_id')
            ->sum('amount');
    }

    /** Create a payout request from the user's payable balance if it clears the minimum. */
    public function requestPayout(User $user, string $method, ?string $details): ?PayoutRequest
    {
        $commissions = $user->commissions()
            ->where('status', 'approved')
            ->whereNull('payout_request_id')
            ->get();

        $amount = (float) $commissions->sum('amount');
        if ($commissions->isEmpty() || $amount < $this->settings()['min_payout']) {
            return null;
        }

        $payout = $user->payoutRequests()->create([
            'amount' => $amount,
            'currency' => $this->settings()['currency'],
            'method' => $method,
            'details' => $details,
            'status' => 'pending',
        ]);

        ReferralCommission::whereIn('id', $commissions->pluck('id'))
            ->update(['payout_request_id' => $payout->id]);

        return $payout;
    }
}
