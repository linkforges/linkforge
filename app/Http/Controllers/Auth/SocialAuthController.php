<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Services\Auth\AbstractOAuthProvider;
use App\Services\Auth\SocialProviders;
use App\Services\Mail\Postman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Provider-agnostic social sign-in / connect (Google, GitHub, Facebook, …).
 * The provider is supplied per-route via ->defaults('provider', …); each
 * provider stores its identity in its own users column (google_id, github_id, …).
 */
class SocialAuthController extends Controller
{
    private function oauth(string $provider): AbstractOAuthProvider
    {
        abort_unless(SocialProviders::has($provider), 404);

        return SocialProviders::make($provider, route("auth.{$provider}.callback"));
    }

    /** Send a guest to the provider to sign in / register. */
    public function redirect(Request $request, string $provider)
    {
        return $this->startFlow($request, $provider, 'login');
    }

    /** Send an authenticated user to the provider to connect their account. */
    public function connect(Request $request, string $provider)
    {
        return $this->startFlow($request, $provider, 'connect');
    }

    private function startFlow(Request $request, string $provider, string $intent)
    {
        $oauth = $this->oauth($provider);
        abort_unless($oauth->enabled(), 404);

        $state = Str::random(40);
        $request->session()->put("{$provider}_oauth_state", $state);
        $request->session()->put("{$provider}_oauth_intent", $intent);

        return redirect()->away($oauth->authUrl($state));
    }

    /** Handle the provider callback for both the sign-in and the connect flows. */
    public function callback(Request $request, string $provider)
    {
        $oauth = $this->oauth($provider);
        abort_unless($oauth->enabled(), 404);

        $label = SocialProviders::label($provider);
        $connect = $request->session()->pull("{$provider}_oauth_intent") === 'connect' && Auth::check();

        if ($request->filled('error')) {
            return $this->fail($connect, $label.' sign-in was cancelled.');
        }

        // CSRF: the state must match the one we issued.
        $expected = $request->session()->pull("{$provider}_oauth_state");
        if (! $expected || ! $request->filled('state') || ! hash_equals($expected, $request->input('state'))) {
            return $this->fail($connect, 'Your '.$label.' session expired. Please try again.');
        }

        if (! $request->filled('code')) {
            return $this->fail($connect, $label.' did not return an authorization code.');
        }

        try {
            $token = $oauth->exchangeCode($request->input('code'));
            $profile = $oauth->fetchProfile($token['access_token']);
        } catch (\Throwable $e) {
            return $this->fail($connect, 'We could not complete '.$label.' sign-in. Please try again.');
        }

        if (empty($profile['email']) || ! $profile['email_verified']) {
            return $this->fail($connect, 'Your '.$label.' account must have a verified email address.');
        }

        return $connect
            ? $this->linkToCurrentUser($request, $provider, $profile)
            : $this->signIn($request, $provider, $profile);
    }

    /** Disconnect a provider from the authenticated account. */
    public function disconnect(Request $request, string $provider)
    {
        abort_unless(SocialProviders::has($provider), 404);
        $user = $request->user();
        $column = SocialProviders::column($provider);
        $label = SocialProviders::label($provider);

        if (! $user->{$column}) {
            return $this->backToConnections("No {$label} account is connected.", true);
        }

        // Don't strand a user who would have no other way to sign in.
        if (! $this->hasOtherLogin($user, $provider)) {
            return $this->backToConnections("Set a password first so you can still sign in after disconnecting {$label}.", true);
        }

        $user->forceFill([$column => null])->save();

        return $this->backToConnections("Your {$label} account has been disconnected.");
    }

    /** Sign-in / sign-up flow (guest). */
    private function signIn(Request $request, string $provider, array $profile)
    {
        $user = $this->resolveUser($provider, $profile);
        if ($user === null) {
            return $this->fail(false, 'New sign-ups are currently disabled.');
        }
        if ($user->status === 'suspended') {
            return $this->fail(false, 'This account has been suspended.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Connect flow: link the provider identity to the already-authenticated user. */
    private function linkToCurrentUser(Request $request, string $provider, array $profile)
    {
        $user = $request->user();
        $column = SocialProviders::column($provider);
        $label = SocialProviders::label($provider);

        $taken = User::where($column, $profile['id'])->where('id', '!=', $user->id)->exists();
        if ($taken) {
            return $this->backToConnections("That {$label} account is already linked to a different account.", true);
        }

        $user->forceFill([$column => $profile['id']])->save();

        return $this->backToConnections("Your {$label} account is now connected.");
    }

    /** Find by provider id, link an existing account by email, or create a new one. */
    private function resolveUser(string $provider, array $profile): ?User
    {
        $column = SocialProviders::column($provider);

        if ($user = User::where($column, $profile['id'])->first()) {
            return $user;
        }

        if ($user = User::where('email', $profile['email'])->first()) {
            $user->forceFill([$column => $profile['id']])->save();

            return $user;
        }

        if (Setting::get('allow_registration', '1') !== '1') {
            return null;
        }

        return $this->createUser($column, $profile);
    }

    private function createUser(string $column, array $profile): User
    {
        $free = Plan::where('slug', 'free')->first();

        $user = User::create([
            'name' => $profile['name'] ?: Str::before($profile['email'], '@'),
            'email' => $profile['email'],
            $column => $profile['id'],
            'password' => null, // OAuth-only; they can set a password later from the account page
            'plan_id' => $free?->id,
            'ai_credits' => (int) ($free?->limit('ai_credits') ?? 0),
        ]);

        $postman = app(Postman::class);
        $postman->send('welcome', $user->email, [
            'name' => $user->name, 'email' => $user->email, 'action_url' => route('dashboard'),
        ]);
        $postman->send('admin_new_user', User::where('role', 'admin')->pluck('email')->all(), [
            'customer_name' => $user->name, 'customer_email' => $user->email, 'action_url' => route('admin.users.show', $user),
        ]);

        return $user;
    }

    /** Can the user still sign in after disconnecting $provider (password, passkey, or another social)? */
    private function hasOtherLogin(User $user, string $provider): bool
    {
        if (! is_null($user->password) || $user->hasPasskeysEnabled()) {
            return true;
        }

        foreach (SocialProviders::keys() as $key) {
            if ($key !== $provider && $user->{SocialProviders::column($key)}) {
                return true;
            }
        }

        return false;
    }

    private function fail(bool $connect, string $message)
    {
        return $connect
            ? $this->backToConnections($message, true)
            : redirect()->route('login')->with('error', $message);
    }

    private function backToConnections(string $message, bool $isError = false)
    {
        return redirect()->route('account', ['tab' => 'security'])->with($isError ? 'error' : 'status', $message);
    }
}
