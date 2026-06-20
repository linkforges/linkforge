<?php

namespace App\Http\Controllers;

use App\Services\Affiliate\ReferralService;
use Illuminate\Http\Request;

/**
 * The member-facing affiliate dashboard: referral link, performance stats,
 * earnings by status, and payout requests. Gated by the affiliate program toggle.
 */
class AffiliateController extends Controller
{
    public function __construct(private ReferralService $referrals) {}

    public function index(Request $request)
    {
        abort_unless($this->referrals->enabled(), 404);
        $user = $request->user();

        return view('affiliate.index', [
            'url' => $this->referrals->referralUrl($user),
            'settings' => $this->referrals->settings(),
            'clicks' => (int) $user->referral_clicks,
            'signups' => $user->referrals()->count(),
            'pending' => $this->referrals->balance($user, ['pending']),
            'approved' => $this->referrals->balance($user, ['approved']),
            'paid' => $this->referrals->balance($user, ['paid']),
            'payable' => $this->referrals->payableBalance($user),
            'commissions' => $user->commissions()->with('referredUser')->latest()->limit(50)->get(),
            'payouts' => $user->payoutRequests()->latest()->get(),
        ]);
    }

    public function payout(Request $request)
    {
        abort_unless($this->referrals->enabled(), 404);

        $data = $request->validate([
            'method' => ['required', 'in:paypal,bank,crypto'],
            'details' => ['required', 'string', 'max:255'],
        ]);

        $payout = $this->referrals->requestPayout($request->user(), $data['method'], $data['details']);

        return back()->with('status', $payout
            ? 'Payout requested for '.$payout->currency.' '.number_format((float) $payout->amount, 2).'.'
            : 'Your approved balance is below the minimum payout amount.');
    }
}
