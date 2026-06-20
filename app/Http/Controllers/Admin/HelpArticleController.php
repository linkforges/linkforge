<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HelpArticleController extends Controller
{
    public function index()
    {
        return view('admin.help.index', [
            'articles' => HelpArticle::orderBy('category')->orderBy('sort')->orderBy('title')->paginate(30),
        ]);
    }

    public function create()
    {
        return view('admin.help.form', ['article' => new HelpArticle(['status' => 'draft', 'category' => 'General'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateArticle($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);
        HelpArticle::create($data);

        return redirect()->route('admin.help.index')->with('status', 'Article saved.');
    }

    public function edit(HelpArticle $article)
    {
        return view('admin.help.form', ['article' => $article]);
    }

    public function update(Request $request, HelpArticle $article)
    {
        $data = $this->validateArticle($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $article->id);
        $article->update($data);

        return redirect()->route('admin.help.index')->with('status', 'Article updated.');
    }

    public function destroy(HelpArticle $article)
    {
        $article->delete();

        return back()->with('status', 'Article deleted.');
    }

    /** @return array<string, mixed> */
    private function validateArticle(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:60000'],
            'status' => ['required', 'in:draft,published'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;
        while (HelpArticle::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
