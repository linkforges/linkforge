@php
    $messages = [
        'inactive' => 'This link has been deactivated by its owner.',
        'expired' => 'This link has expired and is no longer available.',
        'limit' => 'This link has reached its maximum number of clicks.',
    ];
@endphp
<x-public-layout title="Link unavailable">
    <div class="lf-card p-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900">Link unavailable</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $messages[$reason] ?? 'This link cannot be opened right now.' }}</p>
        <a href="/" class="mt-6 inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Go to homepage</a>
    </div>
</x-public-layout>
