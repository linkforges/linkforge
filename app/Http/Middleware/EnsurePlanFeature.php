<?php

namespace App\Http\Middleware;

use App\Services\Billing\PlanGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(
            $request->user() && app(PlanGate::class)->allows($request->user(), $feature),
            403,
            'This feature is not available on your plan.'
        );

        return $next($request);
    }
}
