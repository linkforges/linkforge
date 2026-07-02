<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Opening app…') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.favicon')
    @include('partials.theme')
    @foreach ($pixels as $pixel)
        @include('redirect.partials.pixel', ['pixel' => $pixel])
    @endforeach
</head>
<body class="min-h-screen bg-slate-50">
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6 text-center">
        <x-application-logo size="h-12 w-12" class="text-brand-600" />

        <div class="space-y-4 rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-900/5 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-3">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-50 text-brand-600 shadow-sm">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
                </div>
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ __('Opening the app…') }}</h1>
                    <p class="text-sm leading-6 text-slate-500">{{ __('If your app is installed, it will open automatically. Otherwise you can continue to the website.') }}</p>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 text-left text-sm text-slate-600 shadow-inner shadow-slate-200/80">
                <p class="font-medium text-slate-800">{{ __('Fallback destination') }}</p>
                <p class="truncate text-slate-500">{{ parse_url($target, PHP_URL_HOST) ?: $target }}</p>
            </div>

            <div class="mt-2 flex flex-col items-center gap-3">
                <a id="lf-app" href="{{ $appUrl }}" rel="noopener noreferrer" referrerpolicy="origin-when-cross-origin" class="inline-flex items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/10 transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">{{ __('Open in app') }}</a>
                <a id="lf-web" href="{{ $target }}" rel="noopener noreferrer" referrerpolicy="origin-when-cross-origin" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 hover:text-slate-700">{{ __('Continue to website') }}</a>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var appUrl = @json($appUrl);
            var target = @json($target);

            var timer = setTimeout(function () { window.location.replace(target); }, 2200);

            function cancel() { clearTimeout(timer); }
            document.addEventListener('visibilitychange', function () { if (document.hidden) cancel(); });
            window.addEventListener('pagehide', cancel);
            window.addEventListener('blur', cancel);

            window.location.href = appUrl;
        })();
    </script>

    <script>
        (function () {
            if (!navigator || !navigator.geolocation) return;
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            var token = tokenMeta ? tokenMeta.getAttribute('content') : '';
            var linkId = @json($link_id ?? null);
            if (! linkId) return;

            navigator.geolocation.getCurrentPosition(function (pos) {
                fetch("{{ route('links.clicks.geolocate', ['link' => '__ID__']) }}".replace('__ID__', linkId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify({ lat: pos.coords.latitude, lon: pos.coords.longitude })
                }).catch(function () {});
            }, function () {}, { maximumAge: 60000, timeout: 5000 });
        })();
    </script>

    <script>
        (function () {
            var appUrl = @json($appUrl);
            var target = @json($target);

            // Fall back to the website if the app didn't open (not installed).
            var timer = setTimeout(function () { window.location.replace(target); }, 2200);

            // If the app opens, the page is backgrounded — cancel the web fallback.
            function cancel() { clearTimeout(timer); }
            document.addEventListener('visibilitychange', function () { if (document.hidden) cancel(); });
            window.addEventListener('pagehide', cancel);
            window.addEventListener('blur', cancel);

            // Attempt to launch the app.
            window.location.href = appUrl;
        })();
    </script>
</body>
</html>
