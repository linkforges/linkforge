<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active UI locale for each request, in order of preference:
 *   1. the signed-in user's saved choice (users.settings.locale)
 *   2. the guest cookie (lf_locale)
 *   3. the operator's default locale (config app.locale, overlaid from settings)
 * falling back to the app fallback locale. Only locales the app actually ships
 * (English + any lang/{code}.json) are honoured.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ([
            data_get($request->user()?->settings, 'locale'),
            $request->cookie('lf_locale'),
            config('app.locale'),
        ] as $candidate) {
            if (Locales::isAvailable($candidate)) {
                App::setLocale(strtolower((string) $candidate));

                return $next($request);
            }
        }

        App::setLocale(config('app.fallback_locale', 'en'));

        return $next($request);
    }
}
