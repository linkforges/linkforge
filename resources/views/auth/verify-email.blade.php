<x-guest-layout title="Verify email · LinkForge">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">{{ __('Verify your email') }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ __('Thanks for signing up! Please confirm your email by clicking the link we just sent you.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="lf-btn">{{ __('Resend verification email') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-center text-sm font-medium text-slate-500 hover:text-slate-700">{{ __('Log out') }}</button>
        </form>
    </div>
</x-guest-layout>
