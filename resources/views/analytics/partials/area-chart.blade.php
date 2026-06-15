@php
    $n = count($series);
    $clicksVals = array_map(fn ($r) => $r['clicks'], $series);
    $max = max(1, $clicksVals ? max($clicksVals) : 0);
    $hasData = array_sum($clicksVals) > 0;
    $W = 800;
    $H = 220;
    $pad = 6;

    $coords = [];
    foreach ($series as $i => $r) {
        $x = $n > 1 ? round($i / ($n - 1) * $W, 1) : 0;
        $y = round($H - ($r['clicks'] / $max) * ($H - $pad), 1);
        $coords[] = [$x, $y];
    }

    $line = '';
    foreach ($coords as $i => $c) {
        $line .= ($i === 0 ? 'M' : 'L').$c[0].' '.$c[1].' ';
    }
    $area = $hasData && $n > 0
        ? $line.'L'.$coords[$n - 1][0].' '.$H.' L'.$coords[0][0].' '.$H.' Z'
        : '';
@endphp

@if ($hasData)
    <div class="relative">
        <svg viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" class="h-48 w-full" role="img" aria-label="Clicks over time">
            <defs>
                <linearGradient id="lf-area" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="var(--color-brand-500)" stop-opacity="0.22" />
                    <stop offset="100%" stop-color="var(--color-brand-500)" stop-opacity="0" />
                </linearGradient>
            </defs>
            <path d="{{ $area }}" fill="url(#lf-area)" />
            <path d="{{ $line }}" fill="none" stroke="var(--color-brand-600)" stroke-width="2"
                  vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <span class="pointer-events-none absolute top-0 left-0 text-xs text-slate-400">{{ number_format($max) }}</span>
    </div>
    <div class="mt-2 flex justify-between text-xs text-slate-400">
        <span>{{ \Illuminate\Support\Carbon::parse($series[0]['day'])->format('M j') }}</span>
        <span>{{ \Illuminate\Support\Carbon::parse($series[$n - 1]['day'])->format('M j') }}</span>
    </div>
@else
    <div class="flex h-48 flex-col items-center justify-center text-center">
        <svg class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 14l3-3 3 3 5-6"/></svg>
        <p class="mt-2 text-sm text-slate-400">No clicks in this period yet.</p>
    </div>
@endif
