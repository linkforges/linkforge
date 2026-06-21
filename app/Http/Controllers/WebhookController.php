<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use App\Support\SafeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:255', function ($attr, $value, $fail) {
                if (! SafeUrl::isSafe($value)) {
                    $fail('The endpoint must be a public http(s) URL (internal/loopback addresses are not allowed).');
                }
            }],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['in:link.created,link.clicked,link.flagged'],
        ]);

        $webhook = $request->user()->webhooks()->create([
            'url' => $data['url'],
            'events' => $data['events'],
            'secret' => Str::random(40),
            'is_active' => true,
        ]);

        // Surface the signing secret once so the operator can verify signatures.
        return back()->with('status', 'Webhook endpoint added.')->with('webhook_secret', $webhook->secret);
    }

    public function destroy(Request $request, Webhook $webhook)
    {
        abort_unless((int) $webhook->user_id === (int) $request->user()->id, 403);
        $webhook->delete();

        return back()->with('status', 'Webhook removed.');
    }
}
