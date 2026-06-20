<x-site-layout
    :title="$article->meta_title ?: $article->title"
    :metaDescription="$article->meta_description ?: $article->excerpt"
    :ogTitle="$article->meta_title ?: $article->title"
    :ogDescription="$article->meta_description ?: $article->excerpt"
    ogType="article">

    <article class="mx-auto max-w-3xl px-6 py-12">
        <nav class="flex items-center gap-1.5 text-sm text-slate-400">
            <a href="{{ route('help.index') }}" class="hover:text-slate-600">Help</a>
            <span>/</span>
            <span class="text-slate-500">{{ $article->category }}</span>
        </nav>

        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ $article->title }}</h1>
        @if ($article->excerpt)
            <p class="mt-3 text-lg text-slate-500">{{ $article->excerpt }}</p>
        @endif

        <div class="lf-prose mt-8">{!! $article->rendered_body !!}</div>

        <div class="mt-12 rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-sm text-slate-600">Still need help?</p>
            <a href="{{ route('support.index') }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Contact support</a>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-slate-200/70 bg-white">
            <div class="mx-auto max-w-3xl px-6 py-10">
                <h2 class="mb-4 text-sm font-semibold tracking-wide text-slate-400 uppercase">Related in {{ $article->category }}</h2>
                <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                    @foreach ($related as $r)
                        <li><a href="{{ route('help.show', $r->slug) }}" class="block px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">{{ $r->title }}</a></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-site-layout>
