<?php

namespace App\Http\Controllers;

use App\Services\Billing\PlanGate;
use Illuminate\Http\Request;

class DeveloperController extends Controller
{
    /** Tabs in display order: key => label. */
    public const TABS = [
        'tokens' => 'API tokens',
        'webhooks' => 'Webhooks',
    ];

    /**
     * The developer hub: API tokens and webhooks under one tabbed page.
     * Both datasets are loaded so switching tabs is instant, and the per-item
     * forms (create/revoke) post to their existing routes and redirect back here.
     */
    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'tokens';

        return view('developer.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'tokens' => $request->user()->tokens()->latest()->get(),
            'allowed' => app(PlanGate::class)->allows($request->user(), 'api'),
            'webhooks' => $request->user()->webhooks()->latest()->get(),
        ]);
    }
}
