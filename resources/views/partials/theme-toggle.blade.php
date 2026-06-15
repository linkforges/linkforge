{{-- Light/dark toggle. Persists the choice to localStorage; the no-flash script in
     partials/theme applies it before paint. Safe to include more than once. --}}
<button type="button" data-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode"
        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
    {{-- shown in light mode (click → dark): moon --}}
    <svg data-theme-icon="light" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    {{-- shown in dark mode (click → light): sun --}}
    <svg data-theme-icon="dark" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
</button>

@once
<script>
(function () {
    function sync() {
        var dark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('[data-theme-icon="light"]').forEach(function (e) { e.classList.toggle('hidden', dark); });
        document.querySelectorAll('[data-theme-icon="dark"]').forEach(function (e) { e.classList.toggle('hidden', !dark); });
    }
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-theme-toggle]')) return;
        var dark = !document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark', dark);
        try { localStorage.setItem('lf-theme', dark ? 'dark' : 'light'); } catch (_) {}
        sync();
    });
    sync();
})();
</script>
@endonce
