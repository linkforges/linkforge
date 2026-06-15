<x-admin-layout title="Translate {{ $name }}">
    <x-slot:header>
        <a href="{{ route('admin.languages') }}" class="text-slate-400 transition hover:text-slate-600">Languages</a>
        <span class="text-slate-300">/</span> {{ $name }}
    </x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

    <div id="lf-translator" class="max-w-4xl" data-total="{{ count($source) }}">

        {{-- Toolbar --}}
        <div class="lf-card mb-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1 sm:max-w-xs">
                    <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" id="lf-tr-search" placeholder="Filter strings" autocomplete="off"
                           class="lf-input h-10 w-full pl-9 text-sm">
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id="lf-tr-untranslated" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Untranslated only
                </label>
            </div>
            <p class="text-sm text-slate-500"><span id="lf-tr-count" class="font-semibold text-slate-700">0</span> / {{ count($source) }} translated</p>
        </div>

        @if (count($source) === 0)
            <div class="lf-card p-6 text-sm text-slate-500">
                No translatable strings were found. Use <span class="font-medium text-slate-700">Rescan strings</span> on the
                <a href="{{ route('admin.languages') }}" class="text-brand-600 hover:underline">Languages</a> page first.
            </div>
        @else
            <form method="POST" action="{{ route('admin.languages.update', $code) }}">
                @csrf @method('PUT')

                <div class="lf-card divide-y divide-slate-100 p-0">
                    @foreach ($source as $key => $english)
                        @php $value = $translations[$key] ?? ''; @endphp
                        <div class="lf-tr-row grid gap-2 px-5 py-3.5 sm:grid-cols-2 sm:gap-5" data-haystack="{{ \Illuminate\Support\Str::lower($english) }}">
                            <div class="flex items-start">
                                <p class="text-sm text-slate-600">{{ $english }}</p>
                            </div>
                            <div>
                                <input type="text" name="t[{{ $key }}]" value="{{ $value }}"
                                       @if ($rtl) dir="rtl" @endif
                                       placeholder="{{ $english }}"
                                       class="lf-input lf-tr-input h-10 w-full text-sm {{ $value === '' ? '' : 'lf-tr-filled' }}">
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="sticky bottom-0 z-10 mt-4 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/90 px-5 py-3 backdrop-blur">
                    <p class="text-xs text-slate-500">Blank fields fall back to English.</p>
                    <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Save translations</button>
                </div>
            </form>
        @endif

        {{-- Import --}}
        <details class="lf-card mt-6 p-0">
            <summary class="flex cursor-pointer items-center justify-between px-6 py-4 text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                Import from JSON
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="border-t border-slate-100 px-6 py-5">
                <p class="mb-3 text-sm text-slate-500">
                    Paste a JSON object of <span class="font-mono text-xs">"English string": "translation"</span> pairs.
                    Matching keys are merged in; unknown keys and blanks are ignored. Existing translations are kept unless overwritten.
                </p>
                <form method="POST" action="{{ route('admin.languages.import', $code) }}">
                    @csrf
                    <textarea name="json" rows="6" spellcheck="false" placeholder='{&#10;    "Dashboard": "..."&#10;}'
                              class="lf-input w-full font-mono text-xs"></textarea>
                    @error('json')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-3 flex items-center gap-3">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Import</button>
                        <a href="{{ route('admin.languages.export', 'en') }}" class="text-sm text-brand-600 hover:underline">Download English template</a>
                    </div>
                </form>
            </div>
        </details>
    </div>

    <script>
        (function () {
            const root = document.getElementById('lf-translator');
            if (!root) return;
            const rows = Array.from(root.querySelectorAll('.lf-tr-row'));
            const search = document.getElementById('lf-tr-search');
            const untransOnly = document.getElementById('lf-tr-untranslated');
            const counter = document.getElementById('lf-tr-count');

            function refreshCount() {
                let filled = 0;
                rows.forEach(r => { if (r.querySelector('.lf-tr-input').value.trim() !== '') filled++; });
                if (counter) counter.textContent = filled;
            }

            function applyFilter() {
                const q = (search ? search.value : '').trim().toLowerCase();
                const onlyUntranslated = untransOnly ? untransOnly.checked : false;
                rows.forEach(r => {
                    const input = r.querySelector('.lf-tr-input');
                    const hay = (r.dataset.haystack || '') + ' ' + input.value.toLowerCase();
                    const matchesText = q === '' || hay.indexOf(q) !== -1;
                    const matchesState = !onlyUntranslated || input.value.trim() === '';
                    r.style.display = (matchesText && matchesState) ? '' : 'none';
                });
            }

            if (search) search.addEventListener('input', applyFilter);
            if (untransOnly) untransOnly.addEventListener('change', applyFilter);
            root.addEventListener('input', function (e) {
                if (e.target.classList.contains('lf-tr-input')) refreshCount();
            });

            refreshCount();
        })();
    </script>
</x-admin-layout>
