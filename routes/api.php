<?php

use App\Http\Controllers\Api\LinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 | Developer API (v1). Authenticated with Sanctum personal-access tokens and
 | gated by the plan's "api" feature, then rate limited per token.
 */
Route::middleware(['auth:sanctum', 'plan.feature:api', 'throttle:120,1'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('/me', fn (Request $request) => $request->user()->only(['id', 'name', 'email']));
        Route::apiResource('links', LinkController::class);
    });
