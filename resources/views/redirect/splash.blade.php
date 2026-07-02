<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Redirecting') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.favicon')
    @include('partials.theme')
    @foreach ($pixels as $pixel)
        @include('redirect.partials.pixel', ['pixel' => $pixel])
    @endforeach
</head>
<body class="min-h-screen bg-slate-50">
    @php
        $ad = $ad ?? null;
        $skipSeconds = (int) ($skipSeconds ?? 0);
        // When an ad is shown, hold for at least 3s so it is actually viewable even if the
        // operator left the skip countdown at 0; no ad -> quick hand-off.
        $gate = $ad ? max($skipSeconds, 3) : 0;
    @endphp
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6 text-center">
        <x-application-logo size="h-12 w-12" class="text-brand-600" />

        <div class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-900/5 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-50 text-brand-600 shadow-sm">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div class="space-y-2 text-center">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ __('Redirecting you safely') }}</h1>
                    <p class="text-sm leading-6 text-slate-500">{{ __('We’re finding the best destination for your traffic and will take you there shortly.') }}</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-left text-sm text-slate-600 shadow-inner shadow-slate-200/80">
                <p class="font-medium text-slate-800">{{ __('Destination') }}</p>
                <p class="truncate text-slate-500">{{ parse_url($target, PHP_URL_HOST) ?: $target }}</p>
            </div>

            <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200">
                <div id="lf-progress" class="h-full w-0 rounded-full bg-brand-500 transition-all duration-500 ease-out"></div>
            </div>

            <div class="mt-6 flex flex-col items-center gap-3">
                <a id="lf-continue" href="{{ $target }}"
                   rel="noopener noreferrer"
                   referrerpolicy="origin-when-cross-origin"
                   class="inline-flex items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/10 transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 {{ $gate > 0 ? 'pointer-events-none opacity-50' : '' }}">
                    <span id="lf-continue-label">{{ $gate > 0 ? __('Continue in :n', ['n' => $gate]) : __('Continue now') }}</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <p class="text-xs text-slate-400">{{ __('If redirecting does not start automatically, tap the button above.') }}</p>
            </div>

            <noscript>
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    {{ __('JavaScript is disabled. Use the button to continue to your destination.') }}
                </div>
            </noscript>
        </div>
    </div>

    <script>
        (function () {
            var target = @json($target);
            var gate = {{ $gate }};
            var progress = document.getElementById('lf-progress');
            var btn = document.getElementById('lf-continue');
            var label = document.getElementById('lf-continue-label');
            var nowText = @json(__('Continue now'));
            var inText = @json(__('Continue in :n'));

            function go() { window.location.href = target; }
            if (gate < 1) {
                progress.style.width = '100%';
                setTimeout(go, 1400);
                return;
            }

            var left = gate;
            var total = gate;
            var timer = setInterval(function () {
                left -= 1;
                progress.style.width = Math.round(((total - left) / total) * 100) + '%';
                if (left > 0) {
                    label.textContent = inText.replace(':n', left);
                    return;
                }
                clearInterval(timer);
                label.textContent = nowText;
                btn.classList.remove('pointer-events-none', 'opacity-50');
                progress.style.width = '100%';
                go();
            }, 1000);
        })();
    </script>

    <script>
        (function () {
            // Try to get precise geolocation from the browser and post it back
            // to the server to improve geo accuracy for this click.
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
</body>
</html>
