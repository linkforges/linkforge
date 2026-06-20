<x-admin-layout title="Help Center">
    <x-slot:header>Help Center</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $articles->total() }} {{ \Illuminate\Support\Str::plural('article', $articles->total()) }}</p>
        <a href="{{ route('admin.help.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            New article
        </a>
    </div>

    <div class="lf-card overflow-hidden">
        @if ($articles->isEmpty())
            <p class="px-5 py-12 text-center text-sm text-slate-400">No articles yet. Create your first one.</p>
        @else
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                    <tr><th class="px-5 py-3 font-medium">Title</th><th class="px-5 py-3 font-medium">Category</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Views</th><th class="px-5 py-3 text-right"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($articles as $article)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3"><a href="{{ route('admin.help.edit', $article) }}" class="font-medium text-slate-800 hover:text-brand-700">{{ $article->title }}</a></td>
                            <td class="px-5 py-3 text-slate-500">{{ $article->category }}</td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $article->status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ ucfirst($article->status) }}</span></td>
                            <td class="px-5 py-3 text-slate-500">{{ number_format($article->views) }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    @if ($article->status === 'published')<a href="{{ route('help.show', $article->slug) }}" target="_blank" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="View"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></a>@endif
                                    <a href="{{ route('admin.help.edit', $article) }}" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Edit"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></a>
                                    <form method="POST" action="{{ route('admin.help.destroy', $article) }}" data-confirm="Delete this article?" data-confirm-ok="Delete">@csrf @method('DELETE')<button class="rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Delete"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button></form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-5">{{ $articles->links() }}</div>
</x-admin-layout>
