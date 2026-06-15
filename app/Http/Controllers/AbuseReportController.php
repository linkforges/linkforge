<?php

namespace App\Http\Controllers;

use App\Models\AbuseReport;
use App\Models\Link;
use App\Services\Linking\DomainResolver;
use Illuminate\Http\Request;

class AbuseReportController extends Controller
{
    public function create(Request $request)
    {
        return view('abuse.report', ['alias' => (string) $request->query('alias', '')]);
    }

    public function store(Request $request, DomainResolver $domains)
    {
        $data = $request->validate([
            'alias' => ['nullable', 'string', 'max:190'],
            'reporter_email' => ['nullable', 'email', 'max:190'],
            'reason' => ['required', 'string', 'max:1000'],
            'company' => ['nullable', 'size:0'], // honeypot
        ]);

        $linkId = null;
        if (! empty($data['alias'])) {
            $domain = $domains->resolve($request->getHost());
            $linkId = $domain
                ? Link::where('domain_id', $domain->id)->where('alias', $data['alias'])->value('id')
                : null;
        }

        AbuseReport::create([
            'link_id' => $linkId,
            'reporter_email' => $data['reporter_email'] ?? null,
            'reason' => $data['reason'],
            'status' => 'open',
        ]);

        return redirect()->route('report.create')->with('status', 'Thank you. Our team will review this link.');
    }
}
