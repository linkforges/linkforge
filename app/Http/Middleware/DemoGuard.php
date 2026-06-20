<?php

namespace App\Http\Middleware;

use App\Support\Demo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In demo mode, blocks write requests to sensitive/destructive routes (settings,
 * updater, account credentials, registration, …) while leaving the real features
 * usable. A no-op on normal installs.
 */
class DemoGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Demo::enabled() && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $name = $request->route()?->getName();

            // Cosmetic settings (theme/appearance, SEO) stay editable even though
            // admin.settings is otherwise blocked.
            $allowedSettings = $name === 'admin.settings.update'
                && in_array((string) $request->input('section'), Demo::SAFE_SETTINGS, true);

            if (! $allowedSettings && Demo::blocks($name)) {
                $message = 'This action is disabled in the live demo.';

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 403)
                    : back()->with('error', $message);
            }
        }

        return $next($request);
    }
}
