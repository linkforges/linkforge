<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Fetches and parses an RSS 2.0 or Atom feed into a short list of {title, url}
 * items for a bio "RSS" block. Results are cached so the public page render
 * never blocks on a slow feed, and any failure degrades to an empty list.
 */
class BioRss
{
    /** @return list<array{title:string, url:string}> */
    public static function items(string $url, int $count = 5): array
    {
        $url = trim($url);
        $count = max(1, min($count, 20));

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('~^https?://~i', $url)) {
            return [];
        }

        return Cache::remember('bio_rss:'.md5($url).":{$count}", now()->addMinutes(15), function () use ($url, $count) {
            try {
                $body = Http::timeout(6)->withHeaders(['User-Agent' => 'LinkForge/1.0 (+bio-rss)'])->get($url)->body();
                if ($body === '') {
                    return [];
                }

                $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
                if ($xml === false) {
                    return [];
                }

                // RSS 2.0 → channel->item; Atom → entry.
                $nodes = $xml->channel->item ?? $xml->entry ?? [];
                $items = [];
                foreach ($nodes as $node) {
                    $title = trim((string) ($node->title ?? ''));
                    if ($title === '') {
                        continue;
                    }

                    $link = '';
                    if (isset($node->link['href'])) {           // Atom: <link href="...">
                        $link = (string) $node->link['href'];
                    } elseif (isset($node->link)) {             // RSS: <link>text</link>
                        $link = (string) $node->link;
                    }

                    $items[] = ['title' => $title, 'url' => $link];
                    if (count($items) >= $count) {
                        break;
                    }
                }

                return $items;
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}
