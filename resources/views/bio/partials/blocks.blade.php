@php $existingBlocks = $page?->blocks ?? collect(); @endphp
<div>
    <label class="lf-label">Links</label>
    <div id="bio-blocks" class="space-y-2">
        @foreach ($existingBlocks as $i => $b)
            @include('bio.partials.block-row', ['i' => $i, 'block' => $b])
        @endforeach
    </div>

    <button type="button" id="bio-add" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 transition hover:text-brand-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Add link
    </button>

    <template id="bio-block-template">
        @include('bio.partials.block-row', ['i' => '__INDEX__', 'block' => null])
    </template>
</div>

<script>
    (function () {
        const list = document.getElementById('bio-blocks');
        const tpl = document.getElementById('bio-block-template');
        const addBtn = document.getElementById('bio-add');
        if (!list || !tpl || !addBtn) return;
        let idx = {{ $existingBlocks->count() }};

        addBtn.addEventListener('click', function () {
            const html = tpl.innerHTML.replaceAll('__INDEX__', idx++);
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            list.appendChild(wrap.firstElementChild);
        });
        list.addEventListener('click', function (e) {
            const btn = e.target.closest('.bio-block-remove');
            if (btn) btn.closest('.bio-block').remove();
        });
    })();
</script>
