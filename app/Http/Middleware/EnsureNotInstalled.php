<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the installer routes: once the site is installed the wizard is sealed
 * off and any attempt to reach it bounces back to the app.
 */
class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Installer::isInstalled()) {
            return redirect('/');
        }

        return $next($request);
    }
}
