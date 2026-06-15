<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends every request to the web installer until the site is installed. The
 * installer routes, health check and static assets are exempt so the wizard
 * (and its styling) can load on a fresh, database-less upload.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Installer::isInstalled()
            && ! $request->is('install', 'install/*', 'up', 'build/*', 'vendor/*')) {
            return redirect()->route('install.welcome');
        }

        return $next($request);
    }
}
