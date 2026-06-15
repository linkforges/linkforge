@props(['countries' => [], 'max' => 1])
@php
    $map = require resource_path('data/world_map.php');
    $names = require resource_path('data/countries.php');
    $max = max(1, (int) $max);
    $total = array_sum($countries);
@endphp
<div class="lf-worldmap relative">
    <svg viewBox="{{ $map['viewBox'] }}" class="h-auto w-full select-none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Clicks by country">
        @foreach ($map['countries'] as $code => $d)
            @php
                $v = (int) ($countries[$code] ?? 0);
                if ($v > 0) {
                    $ratio = log($v + 1) / log($max + 1);
                    $shade = $ratio > 0.66 ? '700' : ($ratio > 0.4 ? '600' : ($ratio > 0.2 ? '500' : ($ratio > 0.08 ? '400' : '300')));
                    $fill = 'var(--color-brand-'.$shade.')';
                } else {
                    $fill = 'var(--lf-map-empty)';
                }
                $pct = $total > 0 && $v > 0 ? round($v / $total * 100, 1) : 0;
            @endphp
            <path d="{{ $d }}" fill="{{ $fill }}" stroke="var(--lf-map-stroke)" stroke-width="0.4"
                  class="lf-cty{{ $v > 0 ? ' has' : '' }}"
                  data-code="{{ strtolower($code) }}"
                  data-name="{{ $names[$code] ?? $code }}"
                  data-clicks="{{ $v }}"
                  data-pct="{{ $pct }}"@if ($v > 0) aria-label="{{ $names[$code] ?? $code }}: {{ number_format($v) }} ({{ $pct }}%)"@endif></path>
        @endforeach
    </svg>

    {{-- Floating tooltip (positioned by JS) --}}
    <div class="lf-maptip" aria-hidden="true">
        <img class="lf-maptip-flag" src="" alt="">
        <div>
            <div class="lf-maptip-name"></div>
            <div class="lf-maptip-val"></div>
        </div>
    </div>

    @if ($total > 0)
        <div class="mt-3 flex items-center justify-end gap-2 text-[11px] text-slate-400">
            <span>Less</span>
            <span class="inline-flex overflow-hidden rounded-sm ring-1 ring-slate-200/70">
                <span class="h-2.5 w-5" style="background:var(--color-brand-300)"></span>
                <span class="h-2.5 w-5" style="background:var(--color-brand-400)"></span>
                <span class="h-2.5 w-5" style="background:var(--color-brand-500)"></span>
                <span class="h-2.5 w-5" style="background:var(--color-brand-600)"></span>
                <span class="h-2.5 w-5" style="background:var(--color-brand-700)"></span>
            </span>
            <span>More</span>
        </div>
    @endif
</div>

<style>
    .lf-worldmap .lf-cty { transition: fill .12s ease, opacity .12s ease; }
    .lf-worldmap .lf-cty.has { cursor: pointer; }
    .lf-worldmap .lf-cty.has:hover { opacity: .85; stroke: #0f172a; stroke-width: .8; }
    .lf-worldmap .lf-maptip {
        position: absolute; top: 0; left: 0; z-index: 20; display: none;
        align-items: center; gap: .5rem; padding: .4rem .6rem;
        border-radius: .5rem; background: #0f172a; color: #fff;
        font-size: .75rem; line-height: 1.2; white-space: nowrap;
        box-shadow: 0 8px 24px rgba(2,6,23,.25); pointer-events: none;
        transform: translate(-50%, calc(-100% - 10px));
    }
    .lf-worldmap .lf-maptip.is-on { display: flex; }
    .lf-worldmap .lf-maptip-flag { height: .75rem; width: 1.125rem; border-radius: 2px; object-fit: cover; }
    .lf-worldmap .lf-maptip-flag[hidden] { display: none; }
    .lf-worldmap .lf-maptip-name { font-weight: 600; }
    .lf-worldmap .lf-maptip-val { color: #cbd5e1; font-size: .6875rem; }
</style>

<script>
(function () {
    var root = document.currentScript.closest('.lf-worldmap') || document.querySelector('.lf-worldmap');
    if (!root) return;
    var svg = root.querySelector('svg');
    var tip = root.querySelector('.lf-maptip');
    var flag = tip.querySelector('.lf-maptip-flag');
    var nameEl = tip.querySelector('.lf-maptip-name');
    var valEl = tip.querySelector('.lf-maptip-val');
    var flagBase = @json(asset('vendor/flags'));

    function show(path, e) {
        var ds = path.dataset;
        var clicks = parseInt(ds.clicks || '0', 10);
        nameEl.textContent = ds.name;
        valEl.textContent = clicks > 0
            ? Number(clicks).toLocaleString() + ' clicks · ' + ds.pct + '%'
            : 'No clicks';
        flag.src = flagBase + '/' + ds.code + '.svg';
        flag.hidden = false;
        flag.onerror = function () { flag.hidden = true; };
        tip.classList.add('is-on');
        move(e);
    }
    function move(e) {
        var r = root.getBoundingClientRect();
        var x = e.clientX - r.left;
        var y = e.clientY - r.top;
        x = Math.max(8, Math.min(x, r.width - 8)); // keep within the map
        tip.style.left = x + 'px';
        tip.style.top = y + 'px';
    }
    function hide() { tip.classList.remove('is-on'); }

    svg.addEventListener('mouseover', function (e) {
        var p = e.target.closest('.lf-cty');
        if (p) show(p, e);
    });
    svg.addEventListener('mousemove', function (e) {
        if (e.target.closest('.lf-cty')) move(e);
    });
    svg.addEventListener('mouseleave', hide);
})();
</script>
