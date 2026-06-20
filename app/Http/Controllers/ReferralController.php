<?php

namespace App\Http\Controllers;

use App\Services\Affiliate\ReferralService;
use Illuminate\Http\Request;

/**
 * Public referral link: /ref/{code}. Records the click, drops the attribution
 * cookie, and sends the visitor to registration.
 */
class ReferralController extends Controller
{
    public function track(Request $request, string $code, ReferralService $referrals)
    {
        $referrals->trackClick($code);
        $days = max(1, $referrals->settings()['cookie_days']);

        return redirect()->route('register')
            ->withCookie(cookie('affiliate_ref', $code, $days * 24 * 60));
    }
}
