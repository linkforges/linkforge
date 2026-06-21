<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Published CMS pages flagged to appear in the public footer. Cached (the footer
 * renders on every page) and install-safe (returns empty if the table is absent).
 */
class FooterPages
{
    public static function all(): Collection
    {
        try {
            return Cache::remember('footer_pages', 600, fn () => Page::published()
                ->where('show_in_footer', true)
                ->orderBy('sort')
                ->get(['title', 'slug']));
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function forget(): void
    {
        Cache::forget('footer_pages');
    }
}
