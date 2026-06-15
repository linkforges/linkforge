<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** Switch the active UI language (persisted per-user + via cookie for guests). */
    public function switch(Request $request, string $locale)
    {
        abort_unless(Locales::isAvailable($locale), 404);
        $locale = strtolower($locale);

        if ($user = $request->user()) {
            $user->update(['settings' => array_merge((array) $user->settings, ['locale' => $locale])]);
        }

        return redirect()->back()->withCookie(cookie('lf_locale', $locale, 60 * 24 * 365));
    }
}
