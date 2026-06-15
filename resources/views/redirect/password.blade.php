<x-public-layout title="Password required">
    <div class="lf-card p-8">
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </div>
            <h1 class="text-xl font-semibold text-slate-900">Password required</h1>
            <p class="mt-2 text-sm text-slate-500">This link is protected. Enter the password to continue.</p>
        </div>

        @if ($error)
            <div class="mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ route('link.unlock', $alias) }}" class="mt-5 space-y-4">
            @csrf
            <input type="password" name="password" class="lf-input" placeholder="Enter password" autofocus required>
            <button type="submit" class="lf-btn">Unlock link</button>
        </form>
    </div>
</x-public-layout>
