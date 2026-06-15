@php
    $map = [
        'open' => ['Open', 'bg-amber-50 text-amber-700'],
        'answered' => ['Answered', 'bg-brand-50 text-brand-700'],
        'closed' => ['Closed', 'bg-slate-100 text-slate-500'],
    ];
    [$lbl, $cls] = $map[$status] ?? ['Unknown', 'bg-slate-100 text-slate-500'];
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $lbl }}</span>
