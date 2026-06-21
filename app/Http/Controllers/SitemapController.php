<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** A simple XML sitemap of the public marketing, blog, help and CMS pages. */
    public function index(): Response
    {
        $urls = [url('/'), url('/blog'), url('/help')];

        foreach (Post::published()->latest('published_at')->get() as $post) {
            $urls[] = url('/blog/'.$post->slug);
        }
        foreach (HelpArticle::published()->get() as $article) {
            $urls[] = url('/help/'.$article->slug);
        }
        foreach (Page::published()->get() as $page) {
            $urls[] = url('/page/'.$page->slug);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (array_unique($urls) as $url) {
            $xml .= '  <url><loc>'.e($url).'</loc></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /** robots.txt: allow public crawling, keep the app/admin out, point to the sitemap. */
    public function robots(): Response
    {
        $body = "User-agent: *\n"
            ."Disallow: /admin\n"
            ."Disallow: /api\n"
            ."Disallow: /login\n"
            ."Disallow: /register\n"
            ."Disallow: /dashboard\n"
            ."Allow: /\n\n"
            .'Sitemap: '.url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
