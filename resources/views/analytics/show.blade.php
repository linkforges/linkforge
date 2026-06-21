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
