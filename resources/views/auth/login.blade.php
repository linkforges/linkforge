<x-guest-layout title="Sign in · LinkForge">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">{{ __('Welcome back') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Sign in to your dashboard.') }}</p>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @php $googleOn = config('services.google.enabled') && config('services.google.client_id'); @endphp

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="lf-label">{{ __('Email address') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" class="lf-input" placeholder="you@example.com">
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="lf-label">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('Forgot?') }}</a>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="lf-input" placeholder="••••••••">
            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            {{ __('Remember me for 30 days') }}
        </label>

        <button type="submit" class="lf-btn">{{ __('Sign in') }}</button>
    </form>

    {{-- Alternative sign-in methods. Visible when Google is on (server) or passkeys are supported (JS). --}}
    <div data-alt-auth @class(['space-y-3', 'hidden' => ! $googleOn])>
        <div class="my-5 flex items-center gap-3 text-xs font-medium text-slate-400">
            <span class="h-px flex-1 bg-slate-200"></span>{{ __('OR') }}<span class="h-px flex-1 bg-slate-200"></span>
        </div>

        @include('partials.google-button')

        <div data-pk-login-wrap class="hidden">
            <button type="button" data-pk-login
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <svg class="h-5 w-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12a5 5 0 1 1 9.9 1H22v3m-3 0v3m-3-6v6"/><circle cx="7" cy="12" r="2"/></svg>
                {{ __('Sign in with a passkey') }}
            </button>
            <p data-pk-login-error class="mt-2 hidden text-center text-sm text-red-600">{{ '' }}</p>
        </div>
    </div>

    <p class="mt-6 text-center text-sm text-slate-500">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ __('Create one free') }}</a>
    </p>

    @include('partials.webauthn-js')
    <script>
    (function () {
        var wrap = document.querySelector('[data-pk-login-wrap]');
        if (!wrap || !window.LFPasskey.supported()) return;
        wrap.classList.remove('hidden');
        document.querySelector('[data-alt-auth]')?.classList.remove('hidden'); // ensure the divider shows even if Google is off

        var TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var btn = wrap.querySelector('[data-pk-login]');
        var errEl = wrap.querySelector('[data-pk-login-error]');
        var optionsUrl = @json(url('passkeys/login/options'));
        var loginUrl = @json(url('passkeys/login'));

        btn.addEventListener('click', async function () {
            errEl.classList.add('hidden');
            btn.disabled = true;
            try {
                var optRes = await fetch(optionsUrl, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': TOKEN } });
                if (!optRes.ok) throw new Error('options');
                var options = (await optRes.json()).options;
                var credential = await window.LFPasskey.get(options);
                var remember = !!document.querySelector('input[name="remember"]:checked');
                var res = await fetch(loginUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN },
                    body: JSON.stringify({ credential: credential, remember: remember })
                });
                if (res.ok) { window.location.href = (await res.json()).redirect || '/dashboard'; return; }
                throw new Error('verify');
            } catch (e) {
                if (e && (e.name === 'NotAllowedError' || e.name === 'AbortError')) { btn.disabled = false; return; }
                errEl.textContent = 'We could not sign you in with a passkey. Try your password instead.';
                errEl.classList.remove('hidden');
            } finally {
                btn.disabled = false;
            }
        });
    })();
    </script>
</x-guest-layout>
