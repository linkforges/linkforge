<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;

class HelpController extends Controller
{
    public function index()
    {
        return view('help.index', [
            'groups' => HelpArticle::published()->orderBy('sort')->orderBy('title')->get()->groupBy('category'),
        ]);
    }

    public function show(string $slug)
    {
        $article = HelpArticle::published()->where('slug', $slug)->firstOrFail();
        $article->incrementQuietly('views');

        return view('help.show', [
            'article' => $article,
            'related' => HelpArticle::published()
                ->where('category', $article->category)
                ->where('id', '!=', $article->id)
                ->orderBy('sort')->limit(5)->get(),
        ]);
    }
}
