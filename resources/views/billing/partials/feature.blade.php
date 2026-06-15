{{-- One feature row in a plan card. $ok = included, $label = text, $dark = on the highlighted card. --}}
@php $dark = $dark ?? false; @endphp
<li class="flex items-start gap-2.5 text-sm">
    @if ($ok)
        <svg class="mt-0.5 h-4 w-4 flex-none {{ $dark ? 'text-white' : 'text-brand-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        <span class="{{ $dark ? 'text-white/90' : 'text-slate-600' }}">{{ $label }}</span>
    @else
        <svg class="mt-0.5 h-4 w-4 flex-none {{ $dark ? 'text-white/40' : 'text-slate-300' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        <span class="{{ $dark ? 'text-white/50' : 'text-slate-400 line-through decoration-slate-200' }}">{{ $label }}</span>
    @endif
</li>
