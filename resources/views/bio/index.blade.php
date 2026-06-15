<x-app-layout title="Bio pages">
    <x-slot:header>Bio pages</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif

    <div class="mb-5 flex items-center justify-end">
        <a href="{{ route('bio.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            New bio page
        </a>
    </div>

    @if ($pages->isEmpty())
        <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl text-white" style="background-image:linear-gradient(135deg,var(--color-brand-500),var(--color-brand-700))">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
            </span>
            <h3 class="mt-4 text-lg font-semibold text-slate-900">No bio pages yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-slate-500">Build a link-in-bio page to gather all your links in one branded place.</p>
            <a href="{{ route('bio.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Create a bio page</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($pages as $page)
                <div class="lf-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $page->title ?: '@'.$page->slug }}</p>
                            <p class="truncate text-sm text-brand-700">/{{ $page->slug }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $page->is_published ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $page->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">{{ number_format($page->views) }} views</p>
                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('bio.edit', $page) }}" class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Edit</a>
                        <a href="{{ route('bio.stats', $page) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" title="Analytics">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 14l3-3 3 3 5-6"/></svg>
                        </a>
                        <a href="{{ route('bio.leads', $page) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" title="Leads">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.5 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.5A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.5z"/></svg>
                        </a>
                        @if ($page->is_published)
                            <a href="{{ url('/'.$page->slug) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" title="View">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('bio.destroy', $page) }}" data-confirm="Delete this bio page?" data-confirm-ok="Delete page">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-lg border border-slate-300 bg-white p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Delete">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
