<?php

namespace App\Http\Controllers;

use App\Models\Post;

class BlogController extends Controller
{
    public function index()
    {
        return view('blog.index', [
            'posts' => Post::published()->with('author')->latest('published_at')->latest('id')->paginate(9),
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        return view('blog.show', [
            'post' => $post,
            'related' => Post::published()->where('id', '!=', $post->id)->latest('published_at')->limit(3)->get(),
        ]);
    }
}
