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
                <input id="ai_model" name="ai_model" value="{{ old('ai_model', $s['ai_model'] ?? config('linkforge.ai.model')) }}" class="lf-input font-mono text-xs" placeholder="claude-opus-4-8">
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
                <input id="openrouter_model" name="openrouter_model" value="{{ old('openrouter_model', $s['openrouter_model'] ?? config('linkforge.ai.openrouter.model')) }}" class="lf-input font-mono text-xs" placeholder="anthropic/claude-opus-4">
                <p class="mt-1 text-xs text-slate-400">e.g. <code class="rounded bg-slate-100 px-1 text-[11px]">openai/gpt-4o</code>, <code class="rounded bg-slate-100 px-1 text-[11px]">google/gemini-2.0-flash-001</code>, <code class="rounded bg-slate-100 px-1 text-[11px]">meta-llama/llama-3.3-70b-instruct</code>. You own the API spend.</p>
            </div>
        </div>
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
