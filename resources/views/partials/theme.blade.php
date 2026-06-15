@php $t = config('linkforge.theme'); @endphp
{{-- Runtime theme override: re-themes the whole UI from config/settings without rebuilding assets. --}}
<style>
    :root {
        @foreach ($t['brand'] as $k => $v) --color-brand-{{ $k }}: {{ $v }}; @endforeach
        @foreach ($t['spark'] as $k => $v) --color-spark-{{ $k }}: {{ $v }}; @endforeach
        --font-sans: '{{ $t['font'] }}', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
</style>
{{-- Light/dark scheme: apply before paint to avoid a flash. Explicit choice (localStorage)
     wins, else the operator's default scheme, else the OS preference. --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('lf-theme');
            var def = @json(config('linkforge.theme.scheme', 'system'));
            var pref = (stored === 'light' || stored === 'dark') ? stored : def;
            var dark = pref === 'dark' || (pref !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>
