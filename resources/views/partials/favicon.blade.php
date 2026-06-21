{{-- Brand favicon. A custom favicon uploaded in Appearance settings overrides the
     bundled SVG; otherwise fall back to the scalable default. Setting::get is
     resilient pre-install (returns null), so this stays safe on the installer too. --}}
@php $lfFavicon = \App\Models\Setting::get('brand_favicon'); @endphp
@if ($lfFavicon)
    <link rel="icon" href="{{ $lfFavicon }}">
    <link rel="apple-touch-icon" href="{{ $lfFavicon }}">
@else
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
@endif
