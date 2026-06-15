<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Services\Auth\GoogleOAuth;
use App\Services\Mail\Postman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    private function oauth(): GoogleOAuth
    {
        return new GoogleOAuth(route('auth.google.callback'));
    }

    /** Send the user to Google's consent screen to sign in / register. */
    public function redirect(Request $request)
    {
        return $this->startFlow($request, 'login');
    }

    /** Send an authenticated user to Google to connect their account. */
    public function connect(Request $request)
    {
        return $this->startFlow($request, 'connect');
    }

    private function startFlow(Request $request, string $intent)
    {
        $oauth = $this->oauth();
        abort_unless($oauth->enabled(), 404);

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_intent', $intent);

        return redirect()->away($oauth->authUrl($state));
    }

    /** Handle Google's callback for both the sign-in and the connect flows. */
    public function callback(Request $request)
    {
        $oauth = $this->oauth();
        abort_unless($oauth->enabled(), 404);

        $connect = $request->session()->pull('google_oauth_intent') === 'connect' && Auth::check();

        if ($request->filled('error')) {
            return $this->fail($connect, 'Google sign-in was cancelled.');
        }

        // CSRF: the state must match the one we issued.
        $expected = $request->session()->pull('google_oauth_state');
        if (! $expected || ! $request->filled('state') || ! hash_equals($expected, $request->input('state'))) {
            return $this->fail($connect, 'Your Google session expired. Please try again.');
        }

        if (! $request->filled('code')) {
            return $this->fail($connect, 'Google did not return an authorization code.');
        }

        try {
            $token = $oauth->exchangeCode($request->input('code'));
            $profile = $oauth->fetchProfile($token['access_token']);
        } catch (\Throwable $e) {
            return $this->fail($connect, 'We could not complete Google sign-in. Please try again.');
        }

        if (empty($profile['email']) || ! $profile['email_verified']) {
            return $this->fail($connect, 'Your Google account must have a verified email address.');
        }

        return $connect ? $this->linkToCurrentUser($request, $profile) : $this->signIn($request, $profile);
    }

    /** Disconnect Google from the authenticated account. */
    public function disconnect(Request $request)
    {
        $user = $request->user();

        if (! $user->google_id) {
            return $this->backToConnections('No Google account is connected.', true);
        }

        // Don't strand a user who has no other way to sign in.
        if (is_null($user->password) && ! $user->hasPasskeysEnabled()) {
            return $this->backToConnections('Set a password first so you can still sign in after disconnecting Google.', true);
        }

        $user->forceFill(['google_id' => null])->save();

        return $this->backToConnections('Your Google account has been disconnected.');
    }

    /** Sign-in / sign-up flow (guest). */
    private function signIn(Request $request, array $profile)
    {
        $user = $this->resolveUser($profile);
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

    /** Connect flow: link the Google identity to the already-authenticated user. */
    private function linkToCurrentUser(Request $request, array $profile)
    {
        $user = $request->user();

        $other = User::where('google_id', $profile['sub'])->where('id', '!=', $user->id)->exists();
        if ($other) {
            return $this->backToConnections('That Google account is already linked to a different account.', true);
        }

        $user->forceFill(['google_id' => $profile['sub']])->save();

        return $this->backToConnections('Your Google account is now connected.');
    }

    /**
     * Find by Google id, link an existing account by email, or create a new one.
     *
     * @param  array{sub:string,email:string,name:?string}  $profile
     */
    private function resolveUser(array $profile): ?User
    {
        $user = User::where('google_id', $profile['sub'])->first();
        if ($user) {
            return $user;
        }

        $user = User::where('email', $profile['email'])->first();
        if ($user) {
            $user->forceFill(['google_id' => $profile['sub']])->save();

            return $user;
        }

        if (Setting::get('allow_registration', '1') !== '1') {
            return null;
        }

        return $this->createUser($profile);
    }

    /** @param array{sub:string,email:string,name:?string} $profile */
    private function createUser(array $profile): User
    {
        $free = Plan::where('slug', 'free')->first();

        $user = User::create([
            'name' => $profile['name'] ?: Str::before($profile['email'], '@'),
            'email' => $profile['email'],
            'google_id' => $profile['sub'],
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
