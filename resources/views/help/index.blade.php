<x-site-layout title="Help Center" metaDescription="Guides and answers for getting the most out of {{ config('linkforge.name') }}.">
    <section class="border-b border-slate-200/70 bg-white">
        <div class="mx-auto max-w-3xl px-6 py-14 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Help Center</h1>
            <p class="mx-auto mt-3 max-w-xl text-slate-500">Guides and answers for getting the most out of {{ config('linkforge.name') }}.</p>
            <div class="relative mx-auto mt-6 max-w-md">
                <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="help-search" type="text" placeholder="Search articles…" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-3 pl-9 text-sm text-slate-700 shadow-sm outline-none focus:border-brand-400">
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-5xl px-6 py-12">
        @if ($groups->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 px-6 py-20 text-center">
                <h2 class="text-lg font-semibold text-slate-900">No articles yet</h2>
                <p class="mt-1.5 text-sm text-slate-500">Check back soon.</p>
            </div>
        @else
            <div id="help-groups" class="grid gap-8 sm:grid-cols-2">
                @foreach ($groups as $category => $articles)
                    <div class="help-group" data-category="{{ \Illuminate\Support\Str::lower($category) }}">
                        <h2 class="mb-3 text-sm font-semibold tracking-wide text-slate-400 uppercase">{{ $category }}</h2>
                        <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            @foreach ($articles as $article)
                                <li class="help-item" data-text="{{ \Illuminate\Support\Str::lower($article->title.' '.$article->excerpt) }}">
                                    <a href="{{ route('help.show', $article->slug) }}" class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-slate-50">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-800">{{ $article->title }}</p>
                                            @if ($article->excerpt)<p class="truncate text-xs text-slate-400">{{ $article->excerpt }}</p>@endif
                                        </div>
                                        <svg class="h-4 w-4 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <p id="help-empty" class="hidden py-12 text-center text-sm text-slate-400">No articles match your search.</p>
        @endif
    </div>

    <script>
        (function () {
            var input = document.getElementById('help-search'); if (!input) return;
            var items = [].slice.call(document.querySelectorAll('.help-item'));
            var groups = [].slice.call(document.querySelectorAll('.help-group'));
            var empty = document.getElementById('help-empty');
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase(); var any = false;
                items.forEach(function (li) { var show = !q || li.dataset.text.indexOf(q) !== -1; li.style.display = show ? '' : 'none'; if (show) any = true; });
                groups.forEach(function (g) { var visible = g.querySelectorAll('.help-item:not([style*="none"])').length; g.style.display = visible ? '' : 'none'; });
                empty.classList.toggle('hidden', any);
            });
        })();
    </script>
</x-site-layout>
