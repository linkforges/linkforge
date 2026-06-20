<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\ReferralCommission;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Admin review of affiliate commissions and payout requests: approve / reject
 * earned commissions and mark payout requests paid (which settles the attached
 * commissions) or reject them (returning the commissions to the payable pool).
 */
class AffiliateController extends Controller
{
    public function index()
    {
        return view('admin.affiliate.index', [
            'commissions' => ReferralCommission::with(['referrer', 'referredUser'])->latest()->paginate(20),
            'payouts' => PayoutRequest::with('user')->latest()->limit(50)->get(),
            'totals' => [
                'pending' => (float) ReferralCommission::where('status', 'pending')->sum('amount'),
                'approved' => (float) ReferralCommission::where('status', 'approved')->sum('amount'),
                'paid' => (float) ReferralCommission::where('status', 'paid')->sum('amount'),
            ],
            'currency' => (string) Setting::get('currency', 'USD'),
        ]);
    }

    public function updateCommission(Request $request, ReferralCommission $commission)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,rejected']]);
        $commission->update(['status' => $data['status']]);

        return back()->with('status', 'Commission marked '.$data['status'].'.');
    }

    public function updatePayout(Request $request, PayoutRequest $payout)
    {
        $data = $request->validate(['status' => ['required', 'in:paid,rejected']]);

        if ($data['status'] === 'paid') {
            $payout->update(['status' => 'paid']);
            $payout->commissions()->update(['status' => 'paid']);
        } else {
            $payout->update(['status' => 'rejected']);
            $payout->commissions()->update(['payout_request_id' => null]); // return to the payable pool
        }

        return back()->with('status', 'Payout marked '.$data['status'].'.');
    }
}
