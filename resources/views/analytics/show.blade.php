<x-app-layout title="Link analytics">
    <x-slot:header>Link analytics</x-slot:header>

    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('links.index') }}" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Back to links">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <a href="{{ request()->getScheme().'://'.$link->shortUrl() }}" target="_blank" rel="noopener" class="font-medium text-brand-700 hover:underline">{{ $link->shortUrl() }}</a>
            <div class="truncate text-xs text-slate-400">{{ $link->long_url }}</div>
        </div>
    </div>

    @include('analytics.partials.report')
</x-app-layout>
