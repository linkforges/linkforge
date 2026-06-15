<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('linkforge.name') }} · {{ config('linkforge.tagline') }}</title>
    <meta name="description" content="{{ \App\Models\Setting::get('seo_meta_description') ?: config('linkforge.description') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme')
    @include('partials.head-extra')
</head>
<body class="bg-white text-slate-700">
    {{-- Nav --}}
    <header class="sticky top-0 z-30 border-b border-slate-100 bg-white/80 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <x-application-logo size="h-8 w-8" />
                <span class="text-base font-semibold tracking-tight text-slate-900">{{ config('linkforge.name') }}</span>
            </a>
            <nav class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Go to dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Get started free</a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 opacity-60"
             style="background-image:radial-gradient(40rem 20rem at 50% -10%, var(--color-brand-100), transparent)"></div>
        <div class="mx-auto max-w-3xl px-6 py-20 text-center sm:py-28">
            <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3.5 py-1.5 text-xs font-medium text-brand-700">
                <span class="h-1.5 w-1.5 rounded-full" style="background:var(--color-spark-500)"></span>
                AI-native · safe by design · self-hostable
            </span>
            <h1 class="mt-6 text-4xl font-semibold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">
                Forge links that<br>work harder.
            </h1>
            <p class="mx-auto mt-5 max-w-xl text-lg text-slate-500">
                Branded short links, deep analytics, a QR studio, link-in-bio and an AI assistant,
                with never-blacklisted safety scanning. All on hosting you own.
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    Start forging for free
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Sign in</a>
            </div>

            {{-- URL bar mock --}}
            <div class="mx-auto mt-14 max-w-xl">
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                    <div class="flex flex-1 items-center gap-2 px-3 text-sm text-slate-400">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757"/><path d="M10.81 15.312a4.5 4.5 0 0 1-1.242-7.244l4.5-4.5a4.5 4.5 0 0 1 6.364 6.364l-1.757 1.757"/></svg>
                        paste a long url…
                    </div>
                    <span class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Shorten</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-6xl px-6 pb-24">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $features = [
                    ['t' => 'Branded short links', 'd' => 'Custom domains and aliases with sub-50ms redirects.', 'p' => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757'],
                    ['t' => 'Deep analytics', 'd' => 'Real-time clicks by geo, device and referrer. GDPR-safe.', 'p' => 'M3 3v18h18M7 14l3-3 3 3 5-6'],
                    ['t' => 'Never blacklisted', 'd' => 'Multi-source threat scanning keeps your domain trusted.', 'p' => 'M12 3l8 4v5c0 5-3.4 7.7-8 9-4.6-1.3-8-4-8-9V7z'],
                    ['t' => 'QR studio', 'd' => 'Designed, dynamic QR codes with logos and scan tracking.', 'p' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h.01M20 14v.01M14 20h6v-6'],
                    ['t' => 'AI assistant', 'd' => 'Smart aliases, link-in-bio generation and ask-your-links.', 'p' => 'M12 3v3M12 18v3M5 12H2M22 12h-3M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
                    ['t' => 'You own it', 'd' => 'Self-host on shared hosting. Open, extensible and yours.', 'p' => 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7'],
                ];
            @endphp
            @foreach ($features as $f)
                <div class="lf-card p-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $f['p'] }}"/></svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $f['t'] }}</h3>
                    <p class="mt-1.5 text-sm text-slate-500">{{ $f['d'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <footer class="border-t border-slate-100">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row">
            <div class="flex items-center gap-2.5">
                <x-application-logo size="h-7 w-7" />
                <span class="text-sm font-semibold text-slate-900">{{ config('linkforge.name') }}</span>
            </div>
            <p class="text-xs text-slate-400">&copy; {{ date('Y') }} LinkForge. Built to be owned.</p>
        </div>
    </footer>
</body>
</html>
