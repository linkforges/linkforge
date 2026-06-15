<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Billing\BillingService;
use App\Services\Billing\PlanGate;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing, private PlanGate $gate) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $usage = collect([
            'max_links' => 'Links',
            'max_bio' => 'Bio pages',
            'max_domains' => 'Custom domains',
            'max_qr' => 'QR codes',
        ])->map(fn ($label, $key) => [
            'label' => $label,
            'used' => $this->gate->used($user, $key),
            'limit' => $this->gate->limit($user, $key),
            'percent' => $this->gate->percentUsed($user, $key),
        ])->values();

        return view('billing.index', [
            'plans' => Plan::where('is_active', true)->orderBy('sort')->get(),
            'current' => $user->currentPlan(),
            'subscription' => $user->activeSubscription(),
            'usage' => $usage,
            'gateway' => $this->billing->gateway()->key(),
        ]);
    }

    public function subscribe(Request $request, Plan $plan)
    {
        abort_unless($plan->is_active, 404);

        $url = $this->billing->gateway()->checkout($request->user(), $plan);

        if ($url) {
            return redirect()->away($url);
        }

        return redirect()->route('billing.index')->with('status', "You're now on the {$plan->name} plan.");
    }

    /** Customer returns from a hosted checkout page. */
    public function return(Request $request, string $gateway)
    {
        $plan = $this->billing->namedGateway($gateway)?->handleReturn($request);

        $message = $plan
            ? "Your {$plan->name} plan is now active. Thank you!"
            : 'Thanks! Your payment is being processed; your plan will activate as soon as it is confirmed.';

        return redirect()->route('billing.index')->with('status', $message);
    }

    /** Asynchronous gateway webhook / IPN (unauthenticated; CSRF-exempt). */
    public function webhook(Request $request, string $gateway)
    {
        $this->billing->namedGateway($gateway)?->handleWebhook($request);

        return response('OK', 200);
    }
}
