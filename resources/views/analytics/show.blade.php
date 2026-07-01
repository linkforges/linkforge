<x-app-layout title="Link analytics">
    <x-slot:header>Link analytics</x-slot:header>

    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('links.index') }}" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Back to links">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <a href="{{ request()->getScheme().'://'.$link->shortUrl() }}" target="_blank" rel="noopener" class="font-medium text-brand-700 hover:underline">{{ $link->shortUrl() }}</a>
            <div class="truncate text-xs text-slate-400">{{ $link->long_url }}</div>
        </div>
    </div>

    <div class="lf-card mb-5 border-brand-100 bg-brand-50/70 p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-700">Public analytics dashboard</p>
                <p class="mt-1 text-sm text-brand-700/80">Share this link with anyone to view the analytics without signing in.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="text" value="{{ $link->publicAnalyticsUrl() }}" readonly class="min-w-[280px] rounded-lg border border-brand-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:outline-none">
                    <button type="button" data-copy-public="{{ $link->publicAnalyticsUrl() }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                        Copy URL
                    </button>
                </div>
                <form method="POST" action="{{ route('links.stats.reset', $link) }}" onsubmit="return confirm('Reset analytics for this link? This will delete all recorded clicks and chart data permanently.');" class="self-start sm:self-auto">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6v12M16 6v12M5 6l1 14h12l1-14"/></svg>
                        Reset analytics
                    </button>
                </form>
            </div>
        </div>

    <script>
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-copy-public]');
            if (!btn) return;
            navigator.clipboard.writeText(btn.getAttribute('data-copy-public')).then(function () {
                const label = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = label; }, 1200);
            });
        });
    </script>

    @if ($aiEnabled ?? false)
        <div class="lf-card mb-5 overflow-hidden" data-ai-insight data-ai-insight-url="{{ route('ai.link-insight', $link) }}">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-spark-100 text-spark-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 4.8L18.7 9l-4.8 1.9L12 15.7 10.1 10.9 5.3 9l4.8-1.2L12 3z"/><path d="M19 14l.8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">AI performance summary</h3>
                        <p class="text-xs text-slate-500">A plain-language read on how this link is doing. Uses {{ (int) config('linkforge.ai.cost.ask', 1) }} AI credit.</p>
                    </div>
                </div>
                <button type="button" data-ai-insight-trigger
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span data-ai-insight-label>Summarise</span>
                </button>
            </div>
            <div data-ai-insight-result class="hidden border-t border-slate-100 px-5 py-4 text-sm leading-relaxed text-slate-800"></div>
        </div>

        <script>
            (function () {
                var root = document.querySelector('[data-ai-insight]');
                if (!root || root.dataset.bound) return;
                root.dataset.bound = '1';
                var btn = root.querySelector('[data-ai-insight-trigger]');
                var label = root.querySelector('[data-ai-insight-label]');
                var out = root.querySelector('[data-ai-insight-result]');
                var meta = document.querySelector('meta[name="csrf-token"]');
                var token = meta ? meta.getAttribute('content') : '';

                btn.addEventListener('click', function () {
                    btn.disabled = true; label.textContent = 'Reading';
                    out.classList.remove('hidden');
                    out.className = 'border-t border-slate-100 px-5 py-4 text-sm leading-relaxed text-slate-400';
                    out.textContent = 'Reading this link\'s analytics…';
                    fetch(root.dataset.aiInsightUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, body: d }; }); })
                      .then(function (res) {
                        if (!res.ok) { throw new Error(res.body.message || 'Could not summarise.'); }
                        out.className = 'border-t border-slate-100 px-5 py-4 text-sm leading-relaxed text-slate-800';
                        out.textContent = res.body.summary || 'No summary available.';
                    }).catch(function (e) {
                        out.className = 'border-t border-slate-100 px-5 py-4 text-sm leading-relaxed text-red-600';
                        out.textContent = e.message || 'The AI service is unavailable right now.';
                    }).finally(function () {
                        btn.disabled = false; label.textContent = 'Summarise';
                    });
                });
            })();
        </script>
    @endif

    @include('analytics.partials.report')
</x-app-layout>
