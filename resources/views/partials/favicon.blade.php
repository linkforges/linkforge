{{-- Brand favicon. A custom favicon uploaded in Appearance settings overrides the
     bundled default (derived from public/logo.png). Setting::get is resilient
     pre-install (returns null), so this stays safe on the installer too. --}}
@php $lfFavicon = \App\Models\Setting::get('brand_favicon'); @endphp
@if ($lfFavicon)
    <link rel="icon" href="{{ $lfFavicon }}">
    <link rel="apple-touch-icon" href="{{ $lfFavicon }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@endif
