<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        return view('admin.blog.index', ['posts' => Post::latest()->paginate(20)]);
    }

    public function create()
    {
        return view('admin.blog.form', ['post' => new Post(['status' => 'draft'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePost($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);
        $data['author_id'] = $request->user()->id;
        $data['published_at'] = $this->resolvePublishedAt($data);
        Post::create($data);

        return redirect()->route('admin.blog.index')->with('status', 'Post saved.');
    }

    public function edit(Post $post)
    {
        return view('admin.blog.form', ['post' => $post]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validatePost($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $post->id);
        $data['published_at'] = $this->resolvePublishedAt($data, $post);
        $post->update($data);

        return redirect()->route('admin.blog.index')->with('status', 'Post updated.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return back()->with('status', 'Post deleted.');
    }

    /** @return array<string, mixed> */
    private function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:60000'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);
    }

    private function resolvePublishedAt(array $data, ?Post $post = null): ?Carbon
    {
        if ($data['status'] !== 'published') {
            return null;
        }

        return $post?->published_at ?? now();
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;
        while (Post::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
