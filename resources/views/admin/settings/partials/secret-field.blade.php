@php $set = $secretsSet[$field] ?? false; @endphp
<div>
    <label class="lf-label" for="{{ $field }}">{{ $label }}</label>
    <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="new-password" value=""
           class="lf-input" placeholder="{{ $set ? '•••••••• saved' : ($placeholder ?? 'Not set') }}">
    @if ($set)
        <label class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500">
            <input type="checkbox" name="{{ $field }}_clear" value="1" class="h-3.5 w-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            Clear saved value
        </label>
    @else
        <p class="mt-1 text-xs text-slate-400">{{ $hint ?? 'Leave blank to keep unset.' }}</p>
    @endif
</div>
