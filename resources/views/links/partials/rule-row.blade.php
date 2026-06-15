@php
    $rule = $rule ?? null;
    $type = old("rules.$i.type", $rule?->type ?? 'geo');
    $matchVal = '';
    if ($rule) {
        $mv = $rule->match_value ?? [];
        if ($rule->type === 'time') {
            $matchVal = ($mv['from'] ?? '').'-'.($mv['to'] ?? '');
        } elseif ($rule->type !== 'rotation') {
            $matchVal = implode(',', $mv['values'] ?? []);
        }
    }
    $matchVal = old("rules.$i.match", $matchVal);
@endphp
<div class="lf-rule grid grid-cols-1 gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-12 sm:items-center">
    <select name="rules[{{ $i }}][type]" class="lf-input sm:col-span-3">
        @foreach (['geo' => 'Country', 'device' => 'Device', 'os' => 'OS', 'language' => 'Language', 'time' => 'Time', 'rotation' => 'Rotation A/B'] as $v => $l)
            <option value="{{ $v }}" @selected($type === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <input name="rules[{{ $i }}][match]" value="{{ $matchVal }}" class="lf-input sm:col-span-3" placeholder="US,CA / mobile / 09:00-17:00">
    <input name="rules[{{ $i }}][target_url]" value="{{ old("rules.$i.target_url", $rule?->target_url ?? '') }}" type="url" class="lf-input sm:col-span-4" placeholder="https://destination.com">
    <input name="rules[{{ $i }}][weight]" value="{{ old("rules.$i.weight", $rule?->weight ?? '') }}" type="number" min="1" class="lf-input sm:col-span-1" placeholder="wt" title="Weight (rotation)">
    <button type="button" class="lf-rule-remove flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 sm:col-span-1" aria-label="Remove rule">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
    </button>
</div>
