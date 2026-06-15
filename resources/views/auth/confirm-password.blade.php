<x-guest-layout title="Confirm password · LinkForge">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">{{ __('Confirm your password') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('This is a secure area. Please confirm your password before continuing.') }}</p>
    </div>

    <form method="POST" action="{{ url('/user/confirm-password') }}" class="space-y-5">
        @csrf
        <div>
            <label for="password" class="lf-label">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password"
                   class="lf-input" placeholder="••••••••">
            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="lf-btn">{{ __('Confirm') }}</button>
    </form>
</x-guest-layout>
