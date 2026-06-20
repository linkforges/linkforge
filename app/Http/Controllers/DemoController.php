<?php

namespace App\Http\Controllers;

use App\Support\Demo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One-click sign-in for the public demo (no password). Only active in demo mode.
 */
class DemoController extends Controller
{
    public function login(Request $request, string $role)
    {
        abort_unless(Demo::enabled(), 404);

        $email = $role === 'admin' ? Demo::ADMIN_EMAIL : Demo::USER_EMAIL;
        $user = \App\Models\User::where('email', $email)->first();
        abort_unless($user, 404);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route($role === 'admin' ? 'admin.dashboard' : 'dashboard');
    }
}
