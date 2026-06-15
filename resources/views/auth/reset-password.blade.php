<x-guest-layout title="Set new password · LinkForge">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">{{ __('Set a new password') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Choose a strong password for your account.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="lf-label">{{ __('Email address') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required
                   autocomplete="username" class="lf-input">
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="lf-label">{{ __('New password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="lf-input" placeholder="{{ __('At least 8 characters') }}">
            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="lf-label">{{ __('Confirm new password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password" class="lf-input">
        </div>

        <button type="submit" class="lf-btn">{{ __('Reset password') }}</button>
    </form>
</x-guest-layout>
