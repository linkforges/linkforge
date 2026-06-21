{{-- Sitewide announcement banner (Settings -> General). Customer-facing only,
     dismissible per browser; editing the text re-shows it (the key is a hash). --}}
@php
    $annOn = \App\Models\Setting::get('announcement_enabled') === '1';
    $annText = (string) \App\Models\Setting::get('announcement_text');
@endphp
@if ($annOn && $annText !== '')
    @php
        $annStyle = \App\Models\Setting::get('announcement_style') ?: 'info';
        $annClasses = [
            'info' => 'bg-brand-600 text-white',
            'warning' => 'bg-amber-500 text-white',
            'success' => 'bg-emerald-600 text-white',
        ][$annStyle] ?? 'bg-brand-600 text-white';
        $annKey = 'lf-ann-'.substr(md5($annText), 0, 10);
    @endphp
    <div data-announcement data-ann-key="{{ $annKey }}" class="relative {{ $annClasses }} px-10 py-2.5 text-center text-sm font-medium">
        <span>{!! $annText !!}</span>
        <button type="button" data-ann-dismiss class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 text-white/80 transition hover:bg-white/15 hover:text-white" aria-label="Dismiss announcement">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <script>
        (function () {
            var el = document.querySelector('[data-announcement]');
            if (!el) return;
            var key = el.dataset.annKey;
            try { if (localStorage.getItem(key) === '1') { el.style.display = 'none'; return; } } catch (e) {}
            el.querySelector('[data-ann-dismiss]').addEventListener('click', function () {
                el.style.display = 'none';
                try { localStorage.setItem(key, '1'); } catch (e) {}
            });
        })();
    </script>
@endif
