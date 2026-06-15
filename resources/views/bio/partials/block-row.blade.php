@php
    $block = $block ?? null;
    $label = old("blocks.$i.label", $block?->content['label'] ?? '');
    $url = old("blocks.$i.url", $block?->content['url'] ?? '');
@endphp
<div class="bio-block grid grid-cols-1 gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-12 sm:items-center">
    <input name="blocks[{{ $i }}][label]" value="{{ $label }}" class="lf-input sm:col-span-4" placeholder="Button label">
    <input name="blocks[{{ $i }}][url]" value="{{ $url }}" type="url" class="lf-input sm:col-span-7" placeholder="https://...">
    <button type="button" class="bio-block-remove flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 sm:col-span-1" aria-label="Remove link">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
    </button>
</div>
