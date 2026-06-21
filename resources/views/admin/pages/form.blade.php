<x-admin-layout :title="$page->exists ? 'Edit page' : 'New page'">
    <x-slot:header>{{ $page->exists ? 'Edit page' : 'New page' }}</x-slot:header>

    <a href="{{ route('admin.pages.index') }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Back to pages
    </a>

    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="grid gap-6 lg:grid-cols-[1fr_300px]">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        <div class="lf-card space-y-4 p-6">
            <div>
                <label class="lf-label" for="title">Title</label>
                <input id="title" name="title" value="{{ old('title', $page->title) }}" class="lf-input" required>
                @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="slug">Slug <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" class="lf-input" placeholder="terms">
                @if ($page->exists)<p class="mt-1 text-xs text-slate-400">Public URL: <code class="rounded bg-slate-100 px-1 text-[11px]">/page/{{ $page->slug }}</code></p>@endif
            </div>
            <div>
                <label class="lf-label" for="body">Body <span class="font-normal text-slate-400">(Markdown)</span></label>
                <textarea id="body" name="body" rows="20" class="lf-input font-mono text-xs">{{ old('body', $page->body) }}</textarea>
            </div>
        </div>

        <div class="space-y-5">
            <div class="lf-card space-y-4 p-6">
                <div>
                    <label class="lf-label" for="status">Status</label>
                    <select id="status" name="status" class="lf-input">
                        <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                    </select>
                </div>
                <label class="flex items-center gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="show_in_footer" value="1" @checked(old('show_in_footer', $page->show_in_footer)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                    Show in the public footer
                </label>
                <div>
                    <label class="lf-label" for="sort">Sort order</label>
                    <input id="sort" name="sort" type="number" min="0" value="{{ old('sort', $page->sort ?? 0) }}" class="lf-input">
                </div>
            </div>
            <div class="lf-card space-y-4 p-6">
                <h3 class="text-sm font-semibold text-slate-900">SEO</h3>
                <div>
                    <label class="lf-label" for="meta_title">Meta title</label>
                    <input id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="lf-input">
                </div>
                <div>
                    <label class="lf-label" for="meta_description">Meta description</label>
                    <textarea id="meta_description" name="meta_description" rows="3" class="lf-input">{{ old('meta_description', $page->meta_description) }}</textarea>
                </div>
            </div>
            <button type="submit" class="lf-btn w-full">{{ $page->exists ? 'Update page' : 'Create page' }}</button>
        </div>
    </form>
</x-admin-layout>
