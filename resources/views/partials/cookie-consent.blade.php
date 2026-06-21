{{-- Dismissible cookie-consent notice (Settings -> General). Customer-facing; stays
     hidden until JS confirms the visitor has not already accepted (fail-safe). --}}
@php
    $cookieOn = \App\Models\Setting::get('cookie_consent_enabled') === '1';
    $cookieText = (string) \App\Models\Setting::get('cookie_consent_text');
@endphp
@if ($cookieOn)
    <div data-cookie-consent class="fixed inset-x-3 bottom-3 z-40 mx-auto hidden max-w-2xl flex-col items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-lg sm:flex-row sm:items-center sm:gap-4">
        <p class="flex-1 text-sm text-slate-600">{!! $cookieText ?: 'We use cookies to give you the best experience. By using this site, you accept our use of cookies.' !!}</p>
        <button type="button" data-cookie-accept class="shrink-0 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Got it</button>
    </div>
    <script>
        (function () {
            var el = document.querySelector('[data-cookie-consent]');
            if (!el) return;
            try { if (localStorage.getItem('lf-cookie-consent') === '1') return; } catch (e) {}
            el.classList.remove('hidden');
            el.classList.add('flex');
            el.querySelector('[data-cookie-accept]').addEventListener('click', function () {
                el.style.display = 'none';
                try { localStorage.setItem('lf-cookie-consent', '1'); } catch (e) {}
            });
        })();
    </script>
@endif
