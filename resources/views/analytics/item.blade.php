<x-app-layout :title="$pageTitle">
    <x-slot:header>{{ $pageTitle }}</x-slot:header>

    <div class="mb-5 flex items-center gap-3">
        <a href="{{ $backUrl }}" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Back">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <p class="font-medium text-slate-800">{{ $itemTitle }}</p>
            @isset($itemSubtitle)
                @if (! empty($itemHref))
                    <a href="{{ $itemHref }}" target="_blank" rel="noopener" class="truncate text-xs text-brand-700 hover:underline">{{ $itemSubtitle }}</a>
                @else
                    <div class="truncate text-xs text-slate-400">{{ $itemSubtitle }}</div>
                @endif
            @endisset
        </div>
    </div>

    @if (! empty($notice ?? null))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $notice }}</div>
    @endif

    @include('analytics.partials.report')
</x-app-layout>
