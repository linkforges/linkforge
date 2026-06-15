@props(['title' => '', 'items' => [], 'type' => 'plain'])
@php
    $palette = ['#60a5fa', '#fbbf24', '#34d399', '#f472b6', '#a78bfa', '#fb923c', '#22d3ee', '#f87171', '#a3e635', '#94a3b8', '#2dd4bf', '#c084fc'];
    $items = array_values($items);
    $total = array_sum(array_map(fn ($i) => (int) $i['clicks'], $items));
    $slices = [];
    foreach ($items as $idx => $i) {
        $slices[] = ['value' => (int) $i['clicks'], 'color' => $palette[$idx % count($palette)]];
    }
@endphp
<div class="lf-card p-5">
    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if (empty($items))
        <p class="mt-3 text-sm text-slate-400">No data yet.</p>
    @else
        <div class="mt-4 grid items-center gap-6 sm:grid-cols-[170px_1fr]">
            <div>@include('analytics.partials.pie', ['slices' => $slices])</div>
            <div class="space-y-2">
                @foreach ($items as $idx => $i)
                    @php
                        $color = $palette[$idx % count($palette)];
                        $label = $type === 'lang' ? \App\Support\Locales::name($i['label']) : $i['label'];
                        $pct = $total > 0 ? round($i['clicks'] / $total * 100, 1) : 0;
                    @endphp
                    <div class="flex items-center gap-2.5">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $color }}"></span>
                        @if (in_array($type, ['os', 'browser', 'device'], true))
                            <x-brand-icon :type="$type" :label="$i['label']" class="h-4 w-4 shrink-0" />
                        @endif
                        <span class="truncate text-sm text-slate-600 {{ $type === 'device' ? 'capitalize' : '' }}">{{ $label }}</span>
                        <span class="ml-auto shrink-0 text-sm tabular-nums text-slate-400">{{ number_format($i['clicks']) }} <span class="text-slate-300">({{ $pct }}%)</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
