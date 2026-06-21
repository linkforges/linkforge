{{-- "Ask your links" — natural-language analytics over the rollup tables. --}}
<div class="lf-card mb-5 overflow-hidden" data-ask data-ask-url="{{ route('ai.ask') }}">
    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-gradient-to-r from-spark-50 to-transparent px-5 py-3.5">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-spark-100 text-spark-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 4.8L18.7 9l-4.8 1.9L12 15.7 10.1 10.9 5.3 9l4.8-1.2L12 3z"/><path d="M19 14l.8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14z"/></svg>
        </span>
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Ask your links</h3>
            <p class="text-xs text-slate-500">Ask in plain language. Answers come straight from your own analytics.</p>
        </div>
    </div>

    <div class="p-5">
        <div class="flex flex-col gap-2 sm:flex-row">
            <input type="text" data-ask-input maxlength="300"
                   class="lf-input flex-1" placeholder="e.g. top countries in the last 7 days">
            <button type="button" data-ask-trigger
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60">
                <span data-ask-label>Ask</span>
            </button>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            @foreach (['Which links got the most clicks?', 'Clicks this week vs last week', 'Top countries this month', 'Top cities last 30 days', 'Which devices last 30 days?'] as $example)
                <button type="button" data-ask-example
                        class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">{{ $example }}</button>
            @endforeach
        </div>
        <p class="mt-2 text-[11px] text-slate-400">Answers read your live analytics. Each question uses {{ (int) config('linkforge.ai.cost.ask', 1) }} AI credit.</p>

        <div data-ask-result class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <p data-ask-answer class="text-sm leading-relaxed text-slate-800"></p>
            <div data-ask-bars class="mt-3 space-y-2.5"></div>
        </div>
    </div>

    <script>
        (function () {
            var root = document.querySelector('[data-ask]');
            if (!root || root.dataset.askBound) return;
            root.dataset.askBound = '1';

            var input = root.querySelector('[data-ask-input]');
            var btn = root.querySelector('[data-ask-trigger]');
            var label = root.querySelector('[data-ask-label]');
            var result = root.querySelector('[data-ask-result]');
            var answer = root.querySelector('[data-ask-answer]');
            var bars = root.querySelector('[data-ask-bars]');
            var meta = document.querySelector('meta[name="csrf-token"]');
            var token = meta ? meta.getAttribute('content') : '';

            function render(state, message) {
                result.classList.remove('hidden');
                bars.innerHTML = '';
                answer.className = 'text-sm leading-relaxed ' + (state === 'error' ? 'text-red-600' : 'text-slate-800');
                answer.textContent = message;
            }

            function renderBars(rows) {
                if (!rows || !rows.length) return;
                var max = rows.reduce(function (m, r) { return Math.max(m, r.clicks); }, 1);
                rows.forEach(function (r) {
                    var wrap = document.createElement('div');
                    var head = document.createElement('div');
                    head.className = 'flex items-center justify-between text-sm';
                    var name = document.createElement('span');
                    name.className = 'truncate text-slate-600';
                    name.textContent = r.label;
                    var val = document.createElement('span');
                    val.className = 'shrink-0 pl-3 text-slate-400';
                    val.textContent = r.clicks.toLocaleString();
                    head.appendChild(name); head.appendChild(val);
                    var track = document.createElement('div');
                    track.className = 'mt-1 h-1.5 rounded-full bg-slate-200';
                    var fill = document.createElement('div');
                    fill.className = 'h-1.5 rounded-full bg-brand-500';
                    fill.style.width = Math.max(3, Math.round(r.clicks / max * 100)) + '%';
                    track.appendChild(fill);
                    wrap.appendChild(head); wrap.appendChild(track);
                    bars.appendChild(wrap);
                });
            }

            function ask() {
                var q = input.value.trim();
                if (!q) { input.focus(); return; }

                btn.disabled = true;
                label.textContent = 'Thinking';
                result.classList.remove('hidden');
                bars.innerHTML = '';
                answer.className = 'text-sm leading-relaxed text-slate-400';
                answer.textContent = 'Reading your analytics…';

                fetch(root.dataset.askUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ question: q })
                }).then(function (r) {
                    return r.json().then(function (d) { return { ok: r.ok, body: d }; });
                }).then(function (res) {
                    if (!res.ok) { throw new Error(res.body.message || 'Could not answer that.'); }
                    render('ok', res.body.answer || 'No answer.');
                    if (res.body.data && (res.body.data.kind === 'breakdown' || res.body.data.kind === 'links')) {
                        renderBars(res.body.data.rows);
                    }
                }).catch(function (e) {
                    render('error', e.message || 'The AI service is unavailable right now.');
                }).finally(function () {
                    btn.disabled = false;
                    label.textContent = 'Ask';
                });
            }

            btn.addEventListener('click', ask);
            input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); ask(); } });
            root.querySelectorAll('[data-ask-example]').forEach(function (ex) {
                ex.addEventListener('click', function () { input.value = ex.textContent; ask(); });
            });
        })();
    </script>
</div>
