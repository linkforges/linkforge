@php $src = $source ?? null; $dateQ = $range === 'custom' ? 'from='.$from->toDateString().'&to='.$to->toDateString() : 'range='.$range; @endphp

{{-- Source filter (account analytics only) --}}
@if ($src)
    <div class="mb-4 inline-flex rounded-lg border border-slate-200 bg-white p-1">
        @foreach (['links' => 'Links', 'bio' => 'Bio Pages', 'qr' => 'QR Codes'] as $key => $lbl)
            <a href="{{ request()->url() }}?source={{ $key }}&{{ $dateQ }}" @class(['rounded-md px-4 py-1.5 text-sm font-semibold transition','bg-slate-900 text-white'=>$src===$key,'text-slate-500 hover:text-slate-800'=>$src!==$key])>{{ $lbl }}</a>
        @endforeach
    </div>
@endif

{{-- Range presets + custom range + export --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-3">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
            @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $r => $lbl)
                <a href="{{ request()->url() }}?range={{ $r }}{{ $src ? '&source='.$src : '' }}" @class(['rounded-md px-3 py-1.5 text-sm font-medium transition','bg-brand-50 text-brand-700'=>$range===$r,'text-slate-500 hover:text-slate-800'=>$range!==$r])>{{ $lbl }}</a>
            @endforeach
        </div>

        <form method="GET" action="{{ request()->url() }}"
              class="flex items-center gap-1.5 rounded-lg border p-1 pl-2.5 {{ $range === 'custom' ? 'border-brand-300 bg-brand-50/50' : 'border-slate-200 bg-white' }}">
            @if ($src)<input type="hidden" name="source" value="{{ $src }}">@endif
            <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="border-0 bg-transparent p-1 text-sm text-slate-700 focus:outline-none">
            <span class="text-xs text-slate-400">to</span>
            <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="border-0 bg-transparent p-1 text-sm text-slate-700 focus:outline-none">
            <button type="submit" class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-700">Apply</button>
        </form>
    </div>

    @if (! empty($exportUrl))
        <a href="{{ $exportUrl }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
            Export CSV
        </a>
    @endif
</div>

{{-- Totals --}}
<div class="grid gap-5 sm:grid-cols-3">
    @php
        $totalCards = $totalsCards ?? [
            ['label' => 'Total clicks', 'value' => $totals['clicks']],
            ['label' => 'Unique visitors', 'value' => $totals['uniques']],
            ['label' => 'Bot clicks', 'value' => $totals['bots']],
        ];
    @endphp
    @foreach ($totalCards as $c)
        <div class="lf-card p-5">
            <span class="text-sm font-medium text-slate-500">{{ $c['label'] }}</span>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($c['value']) }}</p>
        </div>
    @endforeach
</div>

{{-- Time series --}}
<div class="lf-card mt-5 p-5">
    <h3 class="text-sm font-semibold text-slate-900">{{ $seriesTitle ?? 'Clicks over time' }}</h3>
    <div class="mt-4">
        @include('analytics.partials.area-chart', ['series' => $series])
    </div>
</div>

{{-- World map + top countries + top cities --}}
@include('analytics.partials.countries', ['countries' => $countries, 'countryMax' => $countryMax, 'cities' => $cities ?? []])

{{-- Platforms / Browsers / Languages (pie + ranked list with logos) --}}
<div class="mt-5">@include('analytics.partials.dimension-card', ['title' => 'Platforms', 'items' => $dims['os'] ?? [], 'type' => 'os'])</div>
<div class="mt-5">@include('analytics.partials.dimension-card', ['title' => 'Browsers', 'items' => $dims['browser'] ?? [], 'type' => 'browser'])</div>
<div class="mt-5">@include('analytics.partials.dimension-card', ['title' => 'Languages', 'items' => $dims['language'] ?? [], 'type' => 'lang'])</div>

{{-- Devices + referrers --}}
<div class="mt-5 grid gap-5 lg:grid-cols-2">
    @include('analytics.partials.dimension-card', ['title' => 'Devices', 'items' => $dims['device'] ?? [], 'type' => 'device'])
    @include('analytics.partials.dimension-card', ['title' => 'Top referrers', 'items' => $dims['referer'] ?? [], 'type' => 'plain'])
</div>
