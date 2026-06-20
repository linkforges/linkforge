<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="affiliate">

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Affiliate program</h3>
        <p class="mb-4 text-xs text-slate-400">Reward members who refer paying customers. Each member gets a referral link and earns a commission when someone they referred upgrades to a paid plan.</p>

        <label class="flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="affiliate_enabled" value="1" @checked(($s['affiliate_enabled'] ?? '0') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>Enable the affiliate program<br><span class="text-xs text-slate-400">When off, the Affiliate menu is hidden and no new commissions are recorded.</span></span>
        </label>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="lf-label" for="affiliate_commission_type">Commission type</label>
                <select id="affiliate_commission_type" name="affiliate_commission_type" class="lf-input">
                    <option value="percent" @selected(($s['affiliate_commission_type'] ?? 'percent') === 'percent')>Percentage of payment</option>
                    <option value="fixed" @selected(($s['affiliate_commission_type'] ?? 'percent') === 'fixed')>Fixed amount per sale</option>
                </select>
            </div>
            <div>
                <label class="lf-label" for="affiliate_commission_value">Commission value</label>
                <input id="affiliate_commission_value" name="affiliate_commission_value" type="number" step="0.01" min="0"
                       value="{{ old('affiliate_commission_value', $s['affiliate_commission_value'] ?? '20') }}" class="lf-input">
                <p class="mt-1 text-xs text-slate-400">Percent (e.g. 20 = 20% of each payment) or a flat amount per converted sale.</p>
                @error('affiliate_commission_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="affiliate_cookie_days">Cookie window (days)</label>
                <input id="affiliate_cookie_days" name="affiliate_cookie_days" type="number" min="1" max="365"
                       value="{{ old('affiliate_cookie_days', $s['affiliate_cookie_days'] ?? '30') }}" class="lf-input">
                <p class="mt-1 text-xs text-slate-400">How long after a click a signup still credits the referrer.</p>
            </div>
            <div>
                <label class="lf-label" for="affiliate_min_payout">Minimum payout</label>
                <input id="affiliate_min_payout" name="affiliate_min_payout" type="number" step="0.01" min="0"
                       value="{{ old('affiliate_min_payout', $s['affiliate_min_payout'] ?? '50') }}" class="lf-input">
                <p class="mt-1 text-xs text-slate-400">Approved balance a member must reach before requesting a payout.</p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="lf-btn">Save affiliate settings</button>
        <a href="{{ route('admin.affiliate') }}" class="text-sm font-medium text-brand-600 hover:underline">Review commissions &amp; payouts →</a>
    </div>
</form>
