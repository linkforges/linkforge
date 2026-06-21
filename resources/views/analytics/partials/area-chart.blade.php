@php
    $n = count($series);
    $clicksVals = array_map(fn ($r) => $r['clicks'], $series);
    $max = max(1, $clicksVals ? max($clicksVals) : 0);
    $hasData = array_sum($clicksVals) > 0;
    $W = 800;
    $H = 220;
    $pad = 6;

    $coords = [];
    $points = []; // overlay data for the hover tooltip: x%, y%, label
    foreach ($series as $i => $r) {
        $x = $n > 1 ? round($i / ($n - 1) * $W, 1) : 0;
        $y = round($H - ($r['clicks'] / $max) * ($H - $pad), 1);
        $coords[] = [$x, $y];
        $c = (int) $r['clicks'];
        $points[] = [
            'x' => $n > 1 ? round($i / ($n - 1) * 100, 2) : 50,
            'y' => round($y / $H * 100, 2),
            'label' => \Illuminate\Support\Carbon::parse($r['day'])->format('M j').' · '.number_format($c).' click'.($c === 1 ? '' : 's'),
        ];
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
    <div class="lf-chart relative" data-points='@json($points)'>
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

        {{-- Hover overlay: vertical guide, point marker and tooltip --}}
        <div class="lf-chart__guide pointer-events-none absolute top-0 hidden h-full w-px -translate-x-1/2 bg-slate-300 dark:bg-slate-600"></div>
        <div class="lf-chart__dot pointer-events-none absolute hidden h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-brand-600 shadow dark:border-slate-900"></div>
        <div class="lf-chart__tip pointer-events-none absolute top-0 left-0 z-10 hidden whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg dark:bg-slate-700"></div>
        <div class="lf-chart__hit absolute inset-0 cursor-crosshair"></div>
    </div>
    <div class="mt-2 flex justify-between text-xs text-slate-400">
        <span>{{ \Illuminate\Support\Carbon::parse($series[0]['day'])->format('M j') }}</span>
        <span>{{ \Illuminate\Support\Carbon::parse($series[$n - 1]['day'])->format('M j') }}</span>
    </div>

    @once
        <script>
        (function () {
            function init(chart) {
                var points;
                try { points = JSON.parse(chart.getAttribute('data-points')); } catch (e) { return; }
                if (!points || !points.length) return;
                var hit = chart.querySelector('.lf-chart__hit');
                var guide = chart.querySelector('.lf-chart__guide');
                var dot = chart.querySelector('.lf-chart__dot');
                var tip = chart.querySelector('.lf-chart__tip');
                if (!hit || !guide || !dot || !tip) return;

                function move(clientX) {
                    var rect = hit.getBoundingClientRect();
                    var t = rect.width ? (clientX - rect.left) / rect.width : 0;
                    var i = Math.max(0, Math.min(points.length - 1, Math.round(t * (points.length - 1))));
                    var p = points[i];
                    guide.style.left = p.x + '%';
                    dot.style.left = p.x + '%';
                    dot.style.top = p.y + '%';
                    tip.style.left = p.x + '%';
                    tip.style.top = p.y + '%';
                    // Flip the tooltip below the point when the point sits near the top.
                    tip.style.transform = (p.y < 28)
                        ? 'translate(-50%, 14px)'
                        : 'translate(-50%, calc(-100% - 10px))';
                    tip.textContent = p.label;
                    guide.classList.remove('hidden');
                    dot.classList.remove('hidden');
                    tip.classList.remove('hidden');
                }
                function hide() {
                    guide.classList.add('hidden');
                    dot.classList.add('hidden');
                    tip.classList.add('hidden');
                }
                hit.addEventListener('mousemove', function (e) { move(e.clientX); });
                hit.addEventListener('mouseleave', hide);
                hit.addEventListener('touchstart', function (e) { if (e.touches[0]) move(e.touches[0].clientX); }, { passive: true });
                hit.addEventListener('touchmove', function (e) { if (e.touches[0]) move(e.touches[0].clientX); }, { passive: true });
            }
            function run() { document.querySelectorAll('.lf-chart').forEach(init); }
            if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', run); } else { run(); }
        })();
        </script>
    @endonce
@else
    <div class="flex h-48 flex-col items-center justify-center text-center">
        <svg class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 14l3-3 3 3 5-6"/></svg>
        <p class="mt-2 text-sm text-slate-400">No clicks in this period yet.</p>
    </div>
@endif
