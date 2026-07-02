<x-public-layout title="Public analytics">
    <div class="w-full max-w-6xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-col gap-3 border-b border-slate-100 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-600">Public analytics</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $link->shortUrl() }}</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $link->long_url }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                <span class="font-medium text-slate-900">{{ number_format($totals['clicks']) }}</span> total clicks
            </div>
        </div>

        @include('analytics.partials.report')

        {{-- Click Log Table --}}
        @include('analytics.partials.click-log', ['clickLogs' => $clickLogs ?? []])
    </div>
</x-public-layout>
