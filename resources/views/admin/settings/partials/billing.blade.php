<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="billing">

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Payment gateway</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="lf-label" for="billing_gateway">Gateway</label>
                <select id="billing_gateway" name="billing_gateway" class="lf-input">
                    @foreach ($gateways as $val => $label)
                        <option value="{{ $val }}" @selected(old('billing_gateway', $s['billing_gateway'] ?? config('linkforge.billing.gateway')) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lf-label" for="billing_currency">Currency</label>
                <input id="billing_currency" name="billing_currency" maxlength="3" value="{{ old('billing_currency', $s['billing_currency'] ?? config('linkforge.billing.currency')) }}" class="lf-input uppercase">
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-400">"Offline" applies plan changes immediately (manual / bank transfer). Pick a provider below and fill in its keys. The active provider falls back to Offline if its keys are missing.</p>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Stripe</h3>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="stripe_key">Publishable key</label>
                <input id="stripe_key" name="stripe_key" value="{{ old('stripe_key', $s['stripe_key'] ?? '') }}" class="lf-input" placeholder="pk_live_...">
            </div>
            @include('admin.settings.partials.secret-field', ['field' => 'stripe_secret', 'label' => 'Secret key', 'placeholder' => 'sk_live_...'])
            @include('admin.settings.partials.secret-field', ['field' => 'stripe_webhook_secret', 'label' => 'Webhook signing secret', 'placeholder' => 'whsec_...'])
            <p class="text-xs text-slate-400">Webhook URL: <code class="rounded bg-slate-100 px-1 text-[11px]">{{ route('billing.webhook', 'stripe') }}</code></p>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">PayPal</h3>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="paypal_client_id">Client ID</label>
                <input id="paypal_client_id" name="paypal_client_id" value="{{ old('paypal_client_id', $s['paypal_client_id'] ?? '') }}" class="lf-input">
            </div>
            @include('admin.settings.partials.secret-field', ['field' => 'paypal_secret', 'label' => 'Secret'])
            <div>
                <label class="lf-label" for="paypal_mode">Environment</label>
                <select id="paypal_mode" name="paypal_mode" class="lf-input">
                    @foreach (['live' => 'Live', 'sandbox' => 'Sandbox'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('paypal_mode', $s['paypal_mode'] ?? config('linkforge.billing.paypal.mode')) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">CoinPayments</h3>
        <p class="mb-4 text-xs text-slate-400">Accept BTC, ETH, LTC and more. The buyer pays in your chosen settlement coin.</p>
        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="lf-label" for="coinpayments_merchant_id">Merchant ID</label>
                    <input id="coinpayments_merchant_id" name="coinpayments_merchant_id" value="{{ old('coinpayments_merchant_id', $s['coinpayments_merchant_id'] ?? '') }}" class="lf-input">
                </div>
                <div>
                    <label class="lf-label" for="coinpayments_receive_currency">Receive coin</label>
                    <input id="coinpayments_receive_currency" name="coinpayments_receive_currency" maxlength="10" value="{{ old('coinpayments_receive_currency', $s['coinpayments_receive_currency'] ?? 'BTC') }}" class="lf-input uppercase" placeholder="BTC">
                </div>
            </div>
            <div>
                <label class="lf-label" for="coinpayments_public_key">Public key</label>
                <input id="coinpayments_public_key" name="coinpayments_public_key" value="{{ old('coinpayments_public_key', $s['coinpayments_public_key'] ?? '') }}" class="lf-input">
            </div>
            @include('admin.settings.partials.secret-field', ['field' => 'coinpayments_private_key', 'label' => 'Private key'])
            @include('admin.settings.partials.secret-field', ['field' => 'coinpayments_ipn_secret', 'label' => 'IPN secret'])
            <p class="text-xs text-slate-400">IPN URL: <code class="rounded bg-slate-100 px-1 text-[11px]">{{ route('billing.webhook', 'coinpayments') }}</code></p>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Crypto.com Pay</h3>
        <div class="space-y-4">
            @include('admin.settings.partials.secret-field', ['field' => 'cryptocom_secret_key', 'label' => 'Secret key', 'placeholder' => 'sk_live_...'])
            @include('admin.settings.partials.secret-field', ['field' => 'cryptocom_webhook_secret', 'label' => 'Webhook signature secret'])
            <p class="text-xs text-slate-400">Webhook URL: <code class="rounded bg-slate-100 px-1 text-[11px]">{{ route('billing.webhook', 'cryptocom') }}</code></p>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save billing</button>
    </div>
</form>
