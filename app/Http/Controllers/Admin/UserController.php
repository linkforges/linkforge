<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'plan' => $request->query('plan', ''),
            'status' => $request->query('status', ''),
            'role' => $request->query('role', ''),
        ];

        $users = $this->query($filters)
            ->with('plan')
            ->withCount(['links', 'bioPages', 'qrCodes'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'plans' => Plan::orderBy('sort')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(User $user)
    {
        $user->loadCount(['links', 'bioPages', 'qrCodes', 'domains']);

        return view('admin.users.show', [
            'user' => $user,
            'plans' => Plan::orderBy('sort')->get(),
            'clicks' => (int) $user->links()->sum('clicks'),
            'subscriptions' => $user->subscriptions()->with('plan')->latest()->take(10)->get(),
            'payments' => $user->payments()->latest()->take(10)->get(),
            'recentLinks' => $user->links()->latest()->take(10)->get(),
            'impersonatable' => ! $user->isAdmin() && $user->id !== request()->user()->id,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'status' => ['required', 'in:active,suspended,pending'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'role' => ['required', 'in:user,admin'],
            'ai_credits' => ['required', 'integer', 'min:0', 'max:100000000'],
        ]);

        // Never let an admin lock themselves out by demoting / suspending their own account.
        $isSelf = (int) $user->id === (int) $request->user()->id;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $isSelf ? 'active' : $data['status'],
            'plan_id' => $data['plan_id'] ?: null,
            'role' => $isSelf ? 'admin' : $data['role'],
            'ai_credits' => $data['ai_credits'],
        ]);
        $user->email_verified_at = $request->boolean('verified') ? ($user->email_verified_at ?? now()) : null;
        $user->save();

        AuditLog::record('user.update', "Updated {$user->email}", $user);

        return back()->with('status', 'User updated.'.($isSelf ? ' (Role and status are locked on your own account.)' : ''));
    }

    /** Email the user a password-reset link (no password is ever handled here). */
    public function sendResetLink(User $user)
    {
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', "Password reset link sent to {$user->email}.");
    }

    /** Sign in as the user, remembering the admin to return to. */
    public function impersonate(Request $request, User $user)
    {
        abort_if($user->isAdmin() || (int) $user->id === (int) $request->user()->id, 403);

        AuditLog::record('user.impersonate', "Impersonated {$user->email}", $user); // record while still admin
        $request->session()->put('impersonator_id', $request->user()->id);
        Auth::login($user);

        return redirect()->route('dashboard')->with('status', "You are now viewing the app as {$user->name}.");
    }

    /** Return to the admin account after impersonating. Routed in the auth group. */
    public function leaveImpersonation(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');
        abort_unless($adminId, 403);

        Auth::loginUsingId($adminId);

        return redirect()->route('admin.users')->with('status', 'Returned to your admin account.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if((int) $user->id === (int) $request->user()->id, 403);

        AuditLog::record('user.delete', "Deleted {$user->email} and all their content", $user);

        DB::transaction(function () use ($user) {
            $user->links()->delete();
            $user->bioPages()->delete();
            $user->qrCodes()->delete();
            $user->qrTemplates()->delete();
            $user->domains()->delete();
            $user->pixels()->delete();
            $user->webhooks()->delete();
            $user->subscriptions()->delete();
            $user->payments()->delete();
            $user->apiKeys()->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        return redirect()->route('admin.users')->with('status', 'User and all their content deleted.');
    }

    public function export(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'plan' => $request->query('plan', ''),
            'status' => $request->query('status', ''),
            'role' => $request->query('role', ''),
        ];

        $rows = $this->query($filters)->with('plan')->get(['id', 'name', 'email', 'role', 'status', 'plan_id', 'ai_credits', 'created_at']);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Role', 'Status', 'Plan', 'AI credits', 'Joined']);
            foreach ($rows as $u) {
                fputcsv($out, [$u->name, $u->email, $u->role, $u->status, $u->plan?->name ?? 'Free', $u->ai_credits, (string) $u->created_at]);
            }
            fclose($out);
        }, 'users.csv', ['Content-Type' => 'text/csv']);
    }

    /** Shared filtered query for the list + export. */
    private function query(array $filters)
    {
        return User::query()
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$filters['q']}%")->orWhere('email', 'like', "%{$filters['q']}%")))
            ->when($filters['plan'] === 'free', fn ($q) => $q->whereNull('plan_id'))
            ->when($filters['plan'] !== '' && $filters['plan'] !== 'free', fn ($q) => $q->where('plan_id', $filters['plan']))
            ->when(in_array($filters['status'], ['active', 'suspended', 'pending'], true), fn ($q) => $q->where('status', $filters['status']))
            ->when(in_array($filters['role'], ['user', 'admin'], true), fn ($q) => $q->where('role', $filters['role']));
    }
}
