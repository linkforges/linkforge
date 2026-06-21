<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    /** Render a published CMS page (Terms, Privacy, Contact, or any custom page). */
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return view('pages.show', ['page' => $page]);
    }
}
