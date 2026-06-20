@php
    $user = auth()->user();
    $tfaState = ! $user->two_factor_secret ? 'disabled' : ($user->two_factor_confirmed_at ? 'enabled' : 'pending');
    $passkeys = $user->passkeys()->orderByDesc('id')->get();
@endphp

{{-- Change password --}}
<form method="POST" action="{{ route('account.password') }}" class="space-y-6">
    @csrf @method('PUT')
    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Change password</h3>
        <p class="mb-4 text-xs text-slate-400">Use a long, unique password you don't use anywhere else.</p>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" class="lf-input" autocomplete="current-password" required>
                @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="password">New password</label>
                <input id="password" name="password" type="password" class="lf-input" autocomplete="new-password" required>
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="lf-input" autocomplete="new-password" required>
            </div>
        </div>
        <div class="mt-5 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Update password</button>
        </div>
    </div>
</form>

{{-- Two-factor authentication --}}
<div id="tfa" class="lf-card mt-6 p-6"
     data-state="{{ $tfaState }}"
     data-enable="{{ url('user/two-factor-authentication') }}"
     data-confirm-url="{{ url('user/confirmed-two-factor-authentication') }}"
     data-qr="{{ url('user/two-factor-qr-code') }}"
     data-secret="{{ url('user/two-factor-secret-key') }}"
     data-recovery="{{ url('user/two-factor-recovery-codes') }}">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Two-factor authentication</h3>
            <p class="mt-1 text-xs text-slate-400">Add a one-time code from an authenticator app (Google Authenticator, 1Password, Authy) on top of your password.</p>
        </div>
        <span data-tfa-badge @class([
            'rounded-full px-2.5 py-1 text-xs font-semibold',
            'bg-brand-50 text-brand-700' => $tfaState === 'enabled',
            'bg-slate-100 text-slate-500' => $tfaState !== 'enabled',
        ])>{{ $tfaState === 'enabled' ? 'Enabled' : 'Disabled' }}</span>
    </div>

    <p data-tfa-error class="mt-3 hidden rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600"></p>

    {{-- Disabled --}}
    <div data-tfa-view="disabled" class="@if ($tfaState !== 'disabled') hidden @endif mt-5">
        <button type="button" data-tfa-enable class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Enable two-factor</button>
    </div>

    {{-- Pending setup --}}
    <div data-tfa-view="pending" class="@if ($tfaState !== 'pending') hidden @endif mt-5">
        <p class="mb-3 text-sm text-slate-600">Scan this QR code with your authenticator app, then enter the 6-digit code it shows to finish.</p>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <div data-tfa-qr class="flex h-44 w-44 flex-none items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-400">Loading…</div>
            <div class="flex-1 space-y-3">
                <div>
                    <p class="lf-label">Or enter this setup key manually</p>
                    <code data-tfa-secret class="block rounded-md bg-slate-50 px-3 py-2 font-mono text-xs break-all text-slate-600">…</code>
                </div>
                <div>
                    <label class="lf-label" for="tfa-code">Verification code</label>
                    <input id="tfa-code" data-tfa-code inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" class="lf-input font-mono tracking-widest">
                </div>
                <div class="flex gap-2">
                    <button type="button" data-tfa-confirm class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Confirm &amp; activate</button>
                    <button type="button" data-tfa-cancel class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Enabled --}}
    <div data-tfa-view="enabled" class="@if ($tfaState !== 'enabled') hidden @endif mt-5">
        <div class="flex items-center gap-2 text-sm text-slate-600">
            <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            Two-factor authentication is on for your account.
        </div>

        <div data-tfa-codes class="mt-4 hidden">
            <p class="lf-label">Recovery codes</p>
            <p class="mb-2 text-xs text-slate-400">Store these somewhere safe. Each can be used once if you lose access to your authenticator.</p>
            <ul data-tfa-codes-list class="grid grid-cols-2 gap-1.5 rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-700"></ul>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" data-tfa-show-codes class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">View recovery codes</button>
            <button type="button" data-tfa-regen class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Regenerate codes</button>
            <button type="button" data-tfa-disable class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">Disable</button>
        </div>
    </div>
</div>

{{-- Passkeys --}}
<div id="passkeys" class="lf-card mt-6 p-6"
     data-options="{{ url('user/passkeys/options') }}"
     data-store="{{ url('user/passkeys') }}"
     data-base="{{ url('user/passkeys') }}">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Passkeys</h3>
            <p class="mt-1 text-xs text-slate-400">Sign in with Touch ID, Windows Hello, or a security key instead of a password. Phishing-resistant and nothing to remember.</p>
        </div>
        <span @class([
            'rounded-full px-2.5 py-1 text-xs font-semibold',
            'bg-brand-50 text-brand-700' => $passkeys->isNotEmpty(),
            'bg-slate-100 text-slate-500' => $passkeys->isEmpty(),
        ])>{{ $passkeys->count() }} active</span>
    </div>

    <p data-pk-unsupported class="mt-3 hidden rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">This browser does not support passkeys.</p>
    <p data-pk-error class="mt-3 hidden rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600"></p>

    @if ($passkeys->isNotEmpty())
        <ul class="mt-4 divide-y divide-slate-100 border-y border-slate-100">
            @foreach ($passkeys as $passkey)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $passkey->name }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $passkey->authenticator ?? 'Passkey' }} &middot; Added {{ $passkey->created_at?->diffForHumans() }}
                            @if ($passkey->last_used_at) &middot; Last used {{ $passkey->last_used_at->diffForHumans() }} @endif
                        </p>
                    </div>
                    <button type="button" data-pk-delete="{{ $passkey->id }}" class="flex-none rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Remove</button>
                </li>
            @endforeach
        </ul>
    @endif

    <div data-pk-add class="mt-4 flex flex-wrap items-end gap-2">
        <div class="min-w-[12rem] flex-1">
            <label class="lf-label" for="pk-name">Name this passkey</label>
            <input id="pk-name" data-pk-name class="lf-input" maxlength="255" placeholder="e.g. My laptop">
        </div>
        <button type="button" data-pk-create class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Add passkey</button>
    </div>
</div>

{{-- Connected accounts --}}
@php
    $enabledProviders = \App\Services\Auth\SocialProviders::enabled();
    $connectedCount = collect(\App\Services\Auth\SocialProviders::keys())
        ->filter(fn ($k) => $user->{\App\Services\Auth\SocialProviders::column($k)})->count();
    $baseLogin = ! is_null($user->password) || $user->hasPasskeysEnabled();
    // Can disconnect this one as long as another sign-in method remains.
    $canDisconnect = $baseLogin || $connectedCount > 1;
    $strandedWarning = false;
@endphp
<div class="lf-card mt-6 p-6">
    <h3 class="text-sm font-semibold text-slate-900">Connected accounts</h3>
    <p class="mt-1 text-xs text-slate-400">Link a social account to sign in with one tap.</p>

    <div class="mt-4 space-y-3">
        @foreach (\App\Services\Auth\SocialProviders::MAP as $key => $meta)
            @php
                $connected = (bool) $user->{$meta['column']};
                $enabled = isset($enabledProviders[$key]);
                if ($connected && ! $canDisconnect) { $strandedWarning = true; }
            @endphp
            @continue (! $connected && ! $enabled)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg border border-slate-200 bg-white">
                        @switch($key)
                            @case('google')
                                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.76h3.57c2.08-1.92 3.27-4.74 3.27-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.76c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.09a6.6 6.6 0 0 1 0-4.18V7.07H2.18a11 11 0 0 0 0 9.86l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"/></svg>
                                @break
                            @case('github')
                                <svg class="h-5 w-5 text-slate-800" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.09.68-.22.68-.49v-1.7c-2.78.62-3.37-1.37-3.37-1.37-.46-1.18-1.11-1.5-1.11-1.5-.91-.63.07-.62.07-.62 1 .07 1.53 1.06 1.53 1.06.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.36-2.22-.26-4.56-1.14-4.56-5.06 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.3.1-2.71 0 0 .84-.27 2.75 1.05a9.36 9.36 0 0 1 5 0c1.91-1.32 2.75-1.05 2.75-1.05.55 1.41.2 2.45.1 2.71.64.72 1.03 1.63 1.03 2.75 0 3.93-2.34 4.79-4.57 5.05.36.32.68.94.68 1.9v2.82c0 .27.18.59.69.49A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z"/></svg>
                                @break
                            @case('facebook')
                                <svg class="h-5 w-5 text-[#1877F2]" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                                @break
                        @endswitch
                    </span>
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ $meta['label'] }}</p>
                        @if ($connected)
                            <p class="flex items-center gap-1 text-xs text-brand-600">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                Connected
                            </p>
                        @else
                            <p class="text-xs text-slate-400">Not connected</p>
                        @endif
                    </div>
                </div>

                @if ($connected)
                    @if ($canDisconnect)
                        <form method="POST" action="{{ route('account.'.$key.'.disconnect') }}"
                              data-confirm="Disconnect your {{ $meta['label'] }} account? You can reconnect it any time." data-confirm-ok="Disconnect">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">Disconnect</button>
                        </form>
                    @else
                        <button type="button" disabled class="cursor-not-allowed rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-300">Disconnect</button>
                    @endif
                @else
                    <a href="{{ route('account.'.$key.'.connect') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Connect</a>
                @endif
            </div>
        @endforeach
    </div>

    @if ($strandedWarning)
        <p class="mt-3 text-xs text-amber-600">Set a password above before disconnecting your only sign-in method, so you can still sign in.</p>
    @endif
</div>

@include('partials.password-confirm')
@include('partials.webauthn-js')

<script>
(function () {
    // ---------- Two-factor ----------
    var tfa = document.getElementById('tfa');
    if (tfa) {
        var d = tfa.dataset;
        var err = tfa.querySelector('[data-tfa-error]');
        var showErr = function (m) { err.textContent = m; err.classList.remove('hidden'); };
        var clearErr = function () { err.classList.add('hidden'); };
        var view = function (n) { return tfa.querySelector('[data-tfa-view="' + n + '"]'); };

        var loadSetup = async function () {
            var qr = await window.lfSecureFetch('GET', d.qr);
            if (!qr || !qr.ok) return;
            tfa.querySelector('[data-tfa-qr]').innerHTML = (await qr.json()).svg;
            var sec = await window.lfSecureFetch('GET', d.secret);
            if (sec && sec.ok) tfa.querySelector('[data-tfa-secret]').textContent = (await sec.json()).secretKey;
        };
        var gotoPending = function () { view('disabled').classList.add('hidden'); view('pending').classList.remove('hidden'); loadSetup(); };

        var bind = function (sel, fn) { var el = tfa.querySelector(sel); if (el) el.addEventListener('click', fn); };

        bind('[data-tfa-enable]', async function () {
            clearErr();
            var res = await window.lfSecureFetch('POST', d.enable);
            if (res && res.ok) gotoPending();
            else if (res) showErr('Could not start two-factor setup. Please try again.');
        });
        bind('[data-tfa-confirm]', async function () {
            clearErr();
            var code = (tfa.querySelector('[data-tfa-code]').value || '').trim();
            if (!code) { showErr('Enter the code from your authenticator app.'); return; }
            var res = await window.lfSecureFetch('POST', d.confirmUrl, { code: code });
            if (res && res.ok) window.location.reload();
            else if (res) showErr('That code was not valid. Try the current code from your app.');
        });
        bind('[data-tfa-cancel]', async function () {
            var res = await window.lfSecureFetch('DELETE', d.enable);
            if (res && res.ok) window.location.reload();
        });
        bind('[data-tfa-disable]', async function () {
            clearErr();
            var res = await window.lfSecureFetch('DELETE', d.enable);
            if (res && res.ok) window.location.reload();
        });

        var renderCodes = function (codes) {
            var list = tfa.querySelector('[data-tfa-codes-list]');
            list.innerHTML = '';
            codes.forEach(function (c) { var li = document.createElement('li'); li.textContent = c; list.appendChild(li); });
            tfa.querySelector('[data-tfa-codes]').classList.remove('hidden');
        };
        bind('[data-tfa-show-codes]', async function () {
            clearErr();
            var res = await window.lfSecureFetch('GET', d.recovery);
            if (res && res.ok) renderCodes(await res.json());
        });
        bind('[data-tfa-regen]', async function () {
            clearErr();
            var res = await window.lfSecureFetch('POST', d.recovery);
            if (res && res.ok) { var r2 = await window.lfSecureFetch('GET', d.recovery); if (r2 && r2.ok) renderCodes(await r2.json()); }
        });

        if (d.state === 'pending') loadSetup();
    }

    // ---------- Passkeys ----------
    var pk = document.getElementById('passkeys');
    if (pk) {
        var pd = pk.dataset;
        var pkErr = pk.querySelector('[data-pk-error]');
        var pkShowErr = function (m) { pkErr.textContent = m; pkErr.classList.remove('hidden'); };
        var pkClearErr = function () { pkErr.classList.add('hidden'); };

        if (!window.LFPasskey.supported()) {
            pk.querySelector('[data-pk-unsupported]').classList.remove('hidden');
            var addBox = pk.querySelector('[data-pk-add]');
            if (addBox) addBox.classList.add('hidden');
        }

        var createBtn = pk.querySelector('[data-pk-create]');
        if (createBtn) createBtn.addEventListener('click', async function () {
            pkClearErr();
            var name = (pk.querySelector('[data-pk-name]').value || '').trim() || 'Passkey';
            createBtn.disabled = true;
            try {
                var optRes = await window.lfSecureFetch('GET', pd.options);
                if (!optRes || !optRes.ok) { if (optRes) pkShowErr('Could not start passkey setup.'); return; }
                var options = (await optRes.json()).options;
                var credential = await window.LFPasskey.create(options);
                var storeRes = await window.lfSecureFetch('POST', pd.store, { name: name, credential: credential });
                if (storeRes && storeRes.ok) window.location.reload();
                else if (storeRes) pkShowErr('We could not register that passkey. Please try again.');
            } catch (e) {
                if (e && e.name !== 'NotAllowedError' && e.name !== 'AbortError') pkShowErr('Passkey setup was cancelled or failed.');
            } finally {
                createBtn.disabled = false;
            }
        });

        pk.querySelectorAll('[data-pk-delete]').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                pkClearErr();
                var res = await window.lfSecureFetch('DELETE', pd.base + '/' + btn.getAttribute('data-pk-delete'));
                if (res && res.ok) window.location.reload();
                else if (res) pkShowErr('Could not remove that passkey.');
            });
        });
    }
})();
</script>
