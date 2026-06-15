<x-guest-layout title="Create account · LinkForge">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">{{ __('Create your account') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Start forging smart links in seconds. No card required.') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div>
            <label for="name" class="lf-label">{{ __('Full name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                   autocomplete="name" class="lf-input" placeholder="Ada Lovelace">
            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="lf-label">{{ __('Email address') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   autocomplete="username" class="lf-input" placeholder="you@example.com">
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="lf-label">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="lf-input" placeholder="{{ __('At least 8 characters') }}">
            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="lf-label">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password" class="lf-input" placeholder="{{ __('Re-enter your password') }}">
        </div>

        {{-- Honeypot: hidden from humans, catches bots. --}}
        <div class="hidden" aria-hidden="true">
            <label>Company <input type="text" name="company" tabindex="-1" autocomplete="off"></label>
        </div>

        @if (config('linkforge.safety.turnstile.site'))
            <div class="cf-turnstile" data-sitekey="{{ config('linkforge.safety.turnstile.site') }}"></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif

        <button type="submit" class="lf-btn">{{ __('Create account') }}</button>
    </form>

    @if (config('services.google.enabled') && config('services.google.client_id'))
        <div class="my-5 flex items-center gap-3 text-xs font-medium text-slate-400">
            <span class="h-px flex-1 bg-slate-200"></span>{{ __('OR') }}<span class="h-px flex-1 bg-slate-200"></span>
        </div>
        @include('partials.google-button')
    @endif

    <p class="mt-6 text-center text-sm text-slate-500">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ __('Sign in') }}</a>
    </p>
</x-guest-layout>
