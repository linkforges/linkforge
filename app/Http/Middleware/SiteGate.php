<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces two admin-controlled site behaviours:
 *  - Registration gate: when registration is closed, /register redirects to login.
 *  - Maintenance mode: shows a maintenance notice to visitors, while admins, auth,
 *    the admin panel, the health check, and public content (short links + bio pages)
 *    keep working so customers' live links never break.
 */
class SiteGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('register') && Setting::get('allow_registration', '1') !== '1') {
            return redirect()->route('login')->with('status', 'New registrations are currently closed.');
        }

        if (Setting::get('maintenance_mode', '0') === '1' && ! $this->bypassesMaintenance($request)) {
            return response()->view('errors.maintenance', [
                'message' => Setting::get('maintenance_message') ?: 'We are performing scheduled maintenance. Please check back soon.',
            ], 503);
        }

        return $next($request);
    }

    private function bypassesMaintenance(Request $request): bool
    {
        $user = $request->user();
        if ($user && $user->isAdmin()) {
            return true;
        }

        $route = $request->route();
        $isPublicContent = ($route && $route->isFallback)        // short-link + bio resolver
            || $request->routeIs('bio.*')
            || $request->routeIs('link.unlock')
            || $request->routeIs('report.*');

        return $isPublicContent
            || $request->is('admin', 'admin/*')
            || $request->routeIs('login')
            || $request->routeIs('logout')
            || $request->is('up');
    }
}
