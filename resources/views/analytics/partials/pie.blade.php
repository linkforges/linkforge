@props(['slices' => [], 'size' => 160])
@php
    $cx = 80; $cy = 80; $r = 78;
    $nonZero = array_values(array_filter($slices, fn ($s) => (float) ($s['value'] ?? 0) > 0));
    $total = array_sum(array_map(fn ($s) => (float) $s['value'], $nonZero));
    $angle = -M_PI / 2; // start at 12 o'clock
    $rendered = [];
    foreach ($nonZero as $s) {
        $frac = $total > 0 ? (float) $s['value'] / $total : 0;
        if (count($nonZero) === 1 || $frac >= 0.99999) {
            $rendered[] = ['circle' => true, 'color' => $s['color']];
            continue;
        }
        $end = $angle + $frac * 2 * M_PI;
        $x0 = $cx + $r * cos($angle); $y0 = $cy + $r * sin($angle);
        $x1 = $cx + $r * cos($end);   $y1 = $cy + $r * sin($end);
        $large = ($end - $angle) > M_PI ? 1 : 0;
        $rendered[] = [
            'd' => sprintf('M%.2f %.2f L%.2f %.2f A%d %d 0 %d 1 %.2f %.2f Z', $cx, $cy, $x0, $y0, $r, $r, $large, $x1, $y1),
            'color' => $s['color'],
        ];
        $angle = $end;
    }
@endphp
@if ($total > 0)
    <svg viewBox="0 0 160 160" style="width:{{ $size }}px;height:{{ $size }}px" class="mx-auto max-w-full shrink-0" aria-hidden="true">
        @foreach ($rendered as $p)
            @if (! empty($p['circle']))
                <circle cx="80" cy="80" r="78" fill="{{ $p['color'] }}"/>
            @else
                <path d="{{ $p['d'] }}" fill="{{ $p['color'] }}" stroke="#ffffff" stroke-width="1.5"/>
            @endif
        @endforeach
    </svg>
@else
    <div style="width:{{ $size }}px;height:{{ $size }}px" class="mx-auto flex shrink-0 items-center justify-center rounded-full bg-slate-50 text-xs text-slate-400">No data yet</div>
@endif
