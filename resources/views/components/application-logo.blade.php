@props(['size' => 'h-9 w-9'])

{{-- Bundled brand mark (public/logo.png) is the default; a custom logo uploaded in
     Appearance settings overrides it via config('linkforge.logo'). --}}
@php $logo = config('linkforge.logo') ?: asset('logo.png'); @endphp
<img src="{{ $logo }}" alt="{{ config('linkforge.name') }}" {{ $attributes->merge(['class' => "shrink-0 object-contain $size !w-auto max-w-[150px]"]) }}>
