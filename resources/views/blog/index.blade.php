<x-site-layout title="Blog" metaDescription="News, guides and product updates.">
    <section class="border-b border-slate-200/70 bg-white">
        <div class="mx-auto max-w-6xl px-6 py-14 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Blog</h1>
            <p class="mx-auto mt-3 max-w-xl text-slate-500">Product updates, link-marketing guides, and tips for getting more from every short link.</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-6 py-12">
        @if ($posts->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 px-6 py-20 text-center">
                <h2 class="text-lg font-semibold text-slate-900">No posts yet</h2>
                <p class="mt-1.5 text-sm text-slate-500">Check back soon.</p>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg hover:shadow-slate-900/5">
                        @if ($post->cover_image)
                            <img src="{{ \Illuminate\Support\Str::startsWith($post->cover_image, 'http') ? $post->cover_image : asset($post->cover_image) }}" alt="" class="aspect-[16/9] w-full object-cover">
                        @else
                            <div class="aspect-[16/9] w-full bg-gradient-to-br from-brand-500/15 to-brand-700/10"></div>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="font-semibold text-slate-900 transition group-hover:text-brand-700">{{ $post->title }}</h2>
                            @if ($post->excerpt)
                                <p class="mt-2 line-clamp-3 flex-1 text-sm text-slate-500">{{ $post->excerpt }}</p>
                            @endif
                            <p class="mt-4 text-xs text-slate-400">
                                {{ optional($post->published_at)->format('M j, Y') ?? $post->created_at->format('M j, Y') }} · {{ $post->reading_time }} min read
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </div>
</x-site-layout>
