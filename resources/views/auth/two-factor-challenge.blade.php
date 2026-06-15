<x-guest-layout title="Two-factor authentication · LinkForge">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">{{ __('Two-factor authentication') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Enter the 6-digit code from your authenticator app to continue.') }}</p>
    </div>

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="code" class="lf-label">{{ __('Authentication code') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus
                   class="lf-input tracking-[0.4em]" placeholder="123456">
            @error('code') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <details class="group">
            <summary class="cursor-pointer text-sm font-medium text-brand-600 hover:text-brand-700 [&::-webkit-details-marker]:hidden">
                {{ __('Use a recovery code instead') }}
            </summary>
            <div class="mt-3">
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                       class="lf-input" placeholder="{{ __('Recovery code') }}">
                @error('recovery_code') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </details>

        <button type="submit" class="lf-btn">{{ __('Verify & continue') }}</button>
    </form>
</x-guest-layout>
