<x-admin-layout :title="$article->exists ? 'Edit article' : 'New article'">
    <x-slot:header>{{ $article->exists ? 'Edit article' : 'New article' }}</x-slot:header>

    <a href="{{ route('admin.help.index') }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Back to articles
    </a>

    <form method="POST" action="{{ $article->exists ? route('admin.help.update', $article) : route('admin.help.store') }}" class="grid gap-6 lg:grid-cols-[1fr_300px]">
        @csrf
        @if ($article->exists) @method('PUT') @endif

        <div class="lf-card p-6 space-y-4">
            <div>
                <label class="lf-label" for="title">Title</label>
                <input id="title" name="title" value="{{ old('title', $article->title) }}" class="lf-input" required>
                @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="slug">Slug <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" class="lf-input" placeholder="how-to-…">
            </div>
            <div>
                <label class="lf-label" for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="2" class="lf-input">{{ old('excerpt', $article->excerpt) }}</textarea>
            </div>
            <div>
                <label class="lf-label" for="body">Body <span class="font-normal text-slate-400">(Markdown)</span></label>
                <textarea id="body" name="body" rows="18" class="lf-input font-mono text-xs">{{ old('body', $article->body) }}</textarea>
            </div>
        </div>

        <div class="space-y-5">
            <div class="lf-card p-6 space-y-4">
                <div>
                    <label class="lf-label" for="category">Category</label>
                    <input id="category" name="category" value="{{ old('category', $article->category) }}" class="lf-input" list="help-categories" required>
                    <datalist id="help-categories">
                        @foreach (\App\Models\HelpArticle::select('category')->distinct()->pluck('category') as $cat)<option value="{{ $cat }}">@endforeach
                    </datalist>
                    <p class="mt-1 text-xs text-slate-400">Articles are grouped by category on the public page.</p>
                </div>
                <div>
                    <label class="lf-label" for="status">Status</label>
                    <select id="status" name="status" class="lf-input">
                        <option value="draft" @selected(old('status', $article->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $article->status) === 'published')>Published</option>
                    </select>
                </div>
                <div>
                    <label class="lf-label" for="sort">Sort order</label>
                    <input id="sort" name="sort" type="number" min="0" value="{{ old('sort', $article->sort ?? 0) }}" class="lf-input">
                </div>
            </div>
            <div class="lf-card p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-900">SEO</h3>
                <div>
                    <label class="lf-label" for="meta_title">Meta title</label>
                    <input id="meta_title" name="meta_title" value="{{ old('meta_title', $article->meta_title) }}" class="lf-input">
                </div>
                <div>
                    <label class="lf-label" for="meta_description">Meta description</label>
                    <textarea id="meta_description" name="meta_description" rows="3" class="lf-input">{{ old('meta_description', $article->meta_description) }}</textarea>
                </div>
            </div>
            <button type="submit" class="lf-btn w-full">{{ $article->exists ? 'Update article' : 'Create article' }}</button>
        </div>
    </form>
</x-admin-layout>
