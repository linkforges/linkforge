@props(['type' => 'plain', 'label' => '', 'class' => 'h-4 w-4'])
@php
    $icon = match ($type) {
        'os' => \App\Support\BrandIcon::os($label),
        'browser' => \App\Support\BrandIcon::browser($label),
        'device' => \App\Support\BrandIcon::device($label),
        default => \App\Support\BrandIcon::get('globe'),
    };
@endphp
@if ($icon['mode'] === 'stroke')
    <svg viewBox="{{ $icon['vb'] }}" class="{{ $class }} text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon['body'] !!}</svg>
@else
    <svg viewBox="{{ $icon['vb'] }}" class="{{ $class }}" fill="{{ $icon['color'] }}" aria-hidden="true">{!! $icon['body'] !!}</svg>
@endif
