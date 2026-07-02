<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers applied to every web response.
 *
 * Note on CSP: the product intentionally injects third-party scripts (retargeting
 * pixels on the splash, bio-page embeds), so a restrictive script-src would break
 * core features. We therefore ship a CSP limited to clickjacking / object / base-uri
 * lockdown (which don't affect those scripts) and leave a nonce-based script-src as
 * documented future hardening. HSTS is only sent over HTTPS.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), browsing-topics=()');
        $headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'");

        if ($request->secure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
