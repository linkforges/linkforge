<x-guest-layout title="Reset password · LinkForge">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">{{ __('Forgot your password?') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __("Enter your email and we'll send you a secure reset link.") }}</p>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="lf-label">{{ __('Email address') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" class="lf-input" placeholder="you@example.com">
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="lf-btn">{{ __('Email password reset link') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">&larr; {{ __('Back to sign in') }}</a>
    </p>
</x-guest-layout>
