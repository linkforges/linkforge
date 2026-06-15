<?php

use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\BillingService;

$billing = app(BillingService::class);

foreach ([['ada@billing.test', 'pro'], ['grace@billing.test', 'starter'], ['alan@billing.test', 'business']] as [$email, $slug]) {
    $u = User::firstOrCreate(['email' => $email], ['name' => ucfirst(explode('@', $email)[0]), 'password' => bcrypt('secret123')]);
    $plan = Plan::where('slug', $slug)->first();
    $billing->activate($u, $plan, 'offline');
}

echo 'active_subs='.App\Models\Subscription::whereIn('status', ['active', 'trialing'])->count().' payments='.App\Models\Payment::count().PHP_EOL;
