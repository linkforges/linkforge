<?php

namespace App\Services\Safety;

use Illuminate\Support\Str;

/**
 * Fast, dependency-free safety checks that run synchronously at create time.
 * Deeper threat-feed scanning is handled by ThreatScanner / the ScanLink job.
 */
class LinkSafety
{
    /** @return string|null  Rejection reason, or null if the URL passes. */
    public function screen(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return 'That URL does not look valid.';
        }

        foreach ((array) config('linkforge.safety.blocked_domains', []) as $blocked) {
            $blocked = strtolower($blocked);
            if ($host === $blocked || Str::endsWith($host, '.'.$blocked)) {
                return 'This destination is not allowed.';
            }
        }

        $haystack = strtolower($url);
        foreach ((array) config('linkforge.safety.blocked_keywords', []) as $keyword) {
            if ($keyword !== '' && str_contains($haystack, strtolower($keyword))) {
                return 'This destination is not allowed.';
            }
        }

        return null;
    }

    public function isDisposableEmail(string $email): bool
    {
        $domain = strtolower(Str::after($email, '@'));

        return in_array($domain, array_map('strtolower', (array) config('linkforge.safety.disposable_domains', [])), true);
    }
}
