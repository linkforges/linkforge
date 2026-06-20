<x-site-layout
    :title="$post->meta_title ?: $post->title"
    :metaDescription="$post->meta_description ?: $post->excerpt"
    :ogTitle="$post->meta_title ?: $post->title"
    :ogDescription="$post->meta_description ?: $post->excerpt"
    :ogImage="$post->cover_image"
    ogType="article">

    <article class="mx-auto max-w-3xl px-6 py-12">
        <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            All posts
        </a>

        <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $post->title }}</h1>
        <p class="mt-3 text-sm text-slate-400">
            @if ($post->author) By {{ $post->author->name }} · @endif
            {{ optional($post->published_at)->format('F j, Y') ?? $post->created_at->format('F j, Y') }} · {{ $post->reading_time }} min read
        </p>

        @if ($post->cover_image)
            <img src="{{ \Illuminate\Support\Str::startsWith($post->cover_image, 'http') ? $post->cover_image : asset($post->cover_image) }}" alt="" class="mt-8 aspect-[16/9] w-full rounded-2xl object-cover">
        @endif

        <div class="lf-prose mt-8">{!! $post->rendered_body !!}</div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-slate-200/70 bg-white">
            <div class="mx-auto max-w-5xl px-6 py-12">
                <h2 class="mb-6 text-lg font-semibold text-slate-900">More from the blog</h2>
                <div class="grid gap-6 sm:grid-cols-3">
                    @foreach ($related as $r)
                        <a href="{{ route('blog.show', $r->slug) }}" class="group rounded-xl border border-slate-200 bg-white p-5 transition hover:shadow-md">
                            <h3 class="font-medium text-slate-900 group-hover:text-brand-700">{{ $r->title }}</h3>
                            <p class="mt-2 text-xs text-slate-400">{{ optional($r->published_at)->format('M j, Y') ?? $r->created_at->format('M j, Y') }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-site-layout>
