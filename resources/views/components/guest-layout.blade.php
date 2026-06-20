<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('linkforge.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme')
    @include('partials.head-extra')
</head>
<body class="relative min-h-screen">
    @include('partials.demo-chrome')
    <div class="absolute right-4 top-4 z-10">@include('partials.locale-switcher')</div>
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Brand showcase --}}
        <div class="relative hidden flex-col justify-between overflow-hidden p-12 text-white lg:flex"
             style="background-image:linear-gradient(160deg,var(--color-brand-700),var(--color-brand-950))">
            <div class="absolute inset-0 opacity-15"
                 style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:22px 22px"></div>

            <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                <x-application-logo size="h-10 w-10" />
                <span class="text-xl font-semibold tracking-tight">{{ config('linkforge.name') }}</span>
            </a>

            <div class="relative">
                <h1 class="text-3xl font-semibold leading-tight">{{ config('linkforge.tagline') }}</h1>
                <p class="mt-3 max-w-md text-brand-100/80">
                    Short links, branded domains, deep analytics, a QR studio and an AI assistant. Safe by design, on hosting you own.
                </p>
                <ul class="mt-8 space-y-3 text-sm">
                    @foreach ([
                        'Lightning-fast branded short links',
                        'Real-time analytics & retargeting pixels',
                        'Never-blacklisted safety scanning',
                        'AI aliases, QR studio & link-in-bio',
                    ] as $feature)
                        <li class="flex items-center gap-3 text-brand-50">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/15">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            </span>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-brand-100/60">&copy; {{ date('Y') }} {{ config('linkforge.name') }}. All rights reserved.</p>
        </div>

        {{-- Form area --}}
        <div class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-application-logo size="h-9 w-9" />
                    <span class="text-lg font-semibold text-slate-900">{{ config('linkforge.name') }}</span>
                </a>
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
