<x-site-layout
    :title="$page->meta_title ?: $page->title"
    :metaDescription="$page->meta_description"
    :ogTitle="$page->meta_title ?: $page->title"
    :ogDescription="$page->meta_description">

    <article class="mx-auto max-w-3xl px-6 py-12 sm:py-16">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $page->title }}</h1>
        <div class="lf-prose mt-8">{!! $page->rendered_body !!}</div>
    </article>
</x-site-layout>
