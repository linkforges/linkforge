@php
    $existingRules = $link
        ? ($link->relationLoaded('rules') ? $link->rules : $link->rules()->orderBy('sort')->get())
        : collect();
@endphp
<details class="rounded-xl border border-slate-200 bg-slate-50/60 p-4" @if($existingRules->isNotEmpty()) open @endif>
    <summary class="cursor-pointer text-sm font-medium text-slate-700 [&::-webkit-details-marker]:hidden">Targeting &amp; rotation</summary>
    <p class="mt-2 text-xs text-slate-400">
        Route visitors to different URLs by country, device, OS, language or time, or split traffic with weighted rotation.
        The first matching targeting rule wins; otherwise rotation applies. Match examples: <code>US,CA</code>, <code>mobile</code>, <code>09:00-17:00</code>.
    </p>

    <div id="lf-rules" class="mt-4 space-y-3">
        @foreach ($existingRules as $i => $rule)
            @include('links.partials.rule-row', ['i' => $i, 'rule' => $rule])
        @endforeach
    </div>

    <button type="button" id="lf-add-rule" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 transition hover:text-brand-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Add rule
    </button>

    <template id="lf-rule-template">
        @include('links.partials.rule-row', ['i' => '__INDEX__', 'rule' => null])
    </template>
</details>

<script>
    (function () {
        const list = document.getElementById('lf-rules');
        const tpl = document.getElementById('lf-rule-template');
        const addBtn = document.getElementById('lf-add-rule');
        if (!list || !tpl || !addBtn) return;
        let idx = {{ $existingRules->count() }};

        addBtn.addEventListener('click', function () {
            const html = tpl.innerHTML.replaceAll('__INDEX__', idx++);
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            list.appendChild(wrap.firstElementChild);
        });

        list.addEventListener('click', function (e) {
            const btn = e.target.closest('.lf-rule-remove');
            if (btn) btn.closest('.lf-rule').remove();
        });
    })();
</script>
