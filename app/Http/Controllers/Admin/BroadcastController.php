<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplatedMail;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\User;
use App\Support\Demo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Send a one-off email to all users, or to everyone on a given plan. Messages are
 * queued (one per recipient) and delivered as the queue drains, so a large list
 * never blocks the request.
 */
class BroadcastController extends Controller
{
    public function index()
    {
        return view('admin.broadcast', [
            'plans' => Plan::orderBy('id')->get(),
            'userCount' => User::whereNotNull('email')->count(),
        ]);
    }

    public function send(Request $request)
    {
        if (Demo::enabled()) {
            return back()->with('error', 'Broadcast is disabled in demo mode.');
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::in(['all', 'plan'])],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        $query = User::query()->whereNotNull('email');
        if ($data['audience'] === 'plan' && ! empty($data['plan_id'])) {
            $query->where('plan_id', $data['plan_id']);
        }

        $count = 0;
        $query->chunkById(200, function ($users) use ($data, &$count) {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(new TemplatedMail($data['subject'], $data['message'], null, null));
                $count++;
            }
        });

        AuditLog::record('broadcast.sent', "Queued a broadcast to {$count} user(s): ".$data['subject']);

        return back()->with('status', "Broadcast queued to {$count} user(s). They are delivered as the queue drains.");
    }
}
