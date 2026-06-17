<?php

namespace App\Http\Controllers;

use App\Services\Billing\PlanGate;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function store(Request $request)
    {
        if (! app(PlanGate::class)->allows($request->user(), 'api')) {
            return back()->with('error', 'API access is available on the Starter plan and above.');
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);

        $token = $request->user()->createToken($data['name']);

        return back()
            ->with('status', 'Token created. Copy it now — it will not be shown again.')
            ->with('plain_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token)
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('status', 'Token revoked.');
    }
}
