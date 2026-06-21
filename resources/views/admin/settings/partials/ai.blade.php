<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="ai">

    @php $aiProvider = old('ai_provider', $s['ai_provider'] ?? config('linkforge.ai.provider', 'anthropic')); @endphp

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">AI provider</h3>
        <p class="mb-4 text-xs text-slate-400">Choose who powers AI features. With the active provider's key unset, every AI feature hides itself and the app behaves as if AI is off.</p>
        <label class="lf-label" for="ai_provider">Provider</label>
        <select id="ai_provider" name="ai_provider" class="lf-input sm:max-w-xs">
            @foreach ($aiProviders as $val => $label)
                <option value="{{ $val }}" @selected($aiProvider === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Anthropic (Claude)</h3>
        <p class="mb-4 text-xs text-slate-400">Native Claude API. Used when the provider above is "Anthropic".</p>
        <div class="space-y-4">
            @include('admin.settings.partials.secret-field', ['field' => 'ai_key', 'label' => 'API key', 'placeholder' => 'sk-ant-...', 'hint' => 'Leave blank to keep unset.'])
            <div>
                <label class="lf-label" for="ai_model">Model</label>
                <input id="ai_model" name="ai_model" list="ai_model_options" value="{{ old('ai_model', $s['ai_model'] ?? config('linkforge.ai.model')) }}" class="lf-input font-mono text-xs" placeholder="claude-haiku-4-5">
                <datalist id="ai_model_options">
                    <option value="claude-haiku-4-5"></option>
                    <option value="claude-sonnet-4-6"></option>
                    <option value="claude-opus-4-8"></option>
                </datalist>
                <p class="mt-1 text-xs text-slate-400">These tasks are simple, so a small model is plenty. <code class="rounded bg-slate-100 px-1 text-[11px]">claude-haiku-4-5</code> is cheapest and fast; <code class="rounded bg-slate-100 px-1 text-[11px]">claude-opus-4-8</code> is most capable.</p>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">OpenRouter</h3>
        <p class="mb-4 text-xs text-slate-400">One key, any model. Used when the provider above is "OpenRouter". Set the model to any OpenRouter slug.</p>
        <div class="space-y-4">
            @include('admin.settings.partials.secret-field', ['field' => 'openrouter_key', 'label' => 'API key', 'placeholder' => 'sk-or-v1-...', 'hint' => 'Leave blank to keep unset.'])
            <div>
                <label class="lf-label" for="openrouter_model">Model</label>
                <input id="openrouter_model" name="openrouter_model" list="openrouter_model_options" value="{{ old('openrouter_model', $s['openrouter_model'] ?? config('linkforge.ai.openrouter.model')) }}" class="lf-input font-mono text-xs" placeholder="openai/gpt-4o-mini">
                <datalist id="openrouter_model_options">
                    <option value="openai/gpt-4o-mini"></option>
                    <option value="openai/gpt-4.1-mini"></option>
                    <option value="google/gemini-2.0-flash-001"></option>
                    <option value="anthropic/claude-3.5-haiku"></option>
                    <option value="meta-llama/llama-3.3-70b-instruct"></option>
                </datalist>
                <p class="mt-1 text-xs text-slate-400">Cheap and capable: <code class="rounded bg-slate-100 px-1 text-[11px]">openai/gpt-4o-mini</code> (default), <code class="rounded bg-slate-100 px-1 text-[11px]">openai/gpt-4.1-mini</code>, <code class="rounded bg-slate-100 px-1 text-[11px]">google/gemini-2.0-flash-001</code>. Or any OpenRouter slug. You own the API spend.</p>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Test connection</h3>
        <p class="mb-4 text-xs text-slate-400">Save your changes first, then send a tiny request to the active provider to confirm the key and model work.</p>
        <button type="button" data-ai-test data-ai-test-url="{{ route('admin.settings.ai.test') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60">
            <span data-ai-test-label>Send test request</span>
        </button>
        <p data-ai-test-result class="mt-3 hidden text-sm"></p>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Credit costs</h3>
        <p class="mb-4 text-xs text-slate-400">Credits charged per AI action (granted per plan).</p>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['ai_cost_alias' => 'Alias suggestion', 'ai_cost_ask' => 'Ask analytics', 'ai_cost_insight' => 'Weekly insight'] as $field => $label)
                <div>
                    <label class="lf-label" for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" value="{{ old($field, $s[$field] ?? config('linkforge.ai.cost.'.str_replace('ai_cost_', '', $field))) }}" class="lf-input">
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save AI</button>
    </div>
</form>

<script>
    (function () {
        var btn = document.querySelector('[data-ai-test]');
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        var label = btn.querySelector('[data-ai-test-label]');
        var out = document.querySelector('[data-ai-test-result]');
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = meta ? meta.getAttribute('content') : '';

        btn.addEventListener('click', function () {
            btn.disabled = true;
            label.textContent = 'Testing…';
            out.classList.add('hidden');
            fetch(btn.dataset.aiTestUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (d) {
                out.className = 'mt-3 text-sm ' + (d.ok ? 'text-emerald-600' : 'text-red-600');
                out.textContent = d.message || (d.ok ? 'OK' : 'Failed');
            }).catch(function () {
                out.className = 'mt-3 text-sm text-red-600';
                out.textContent = 'Could not reach the server.';
            }).finally(function () {
                out.classList.remove('hidden');
                btn.disabled = false;
                label.textContent = 'Send test request';
            });
        });
    })();
</script>
