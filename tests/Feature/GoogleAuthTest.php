<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // Enabled + configured by default; individual tests override as needed.
        config([
            'services.google.enabled' => true,
            'services.google.client_id' => 'cid.apps.googleusercontent.com',
            'services.google.client_secret' => 'GOCSPX-secret',
        ]);
    }

    private function fakeGoogle(array $profile): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.token', 'expires_in' => 3599]),
            'openidconnect.googleapis.com/*' => Http::response(array_merge([
                'sub' => 'g-0001', 'email' => 'someone@gmail.test', 'email_verified' => true, 'name' => 'Some One',
            ], $profile)),
        ]);
    }

    public function test_routes_are_404_when_disabled(): void
    {
        config(['services.google.enabled' => false]);

        $this->get(route('auth.google.redirect'))->assertNotFound();
        $this->get(route('auth.google.callback', ['code' => 'x', 'state' => 'y']))->assertNotFound();
    }

    public function test_redirect_sends_user_to_google_with_state(): void
    {
        $res = $this->get(route('auth.google.redirect'));

        $res->assertRedirect();
        $location = $res->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $location);
        $this->assertStringContainsString('client_id=cid.apps', urldecode($location));
        $this->assertStringContainsString('scope=openid email profile', urldecode($location));
        $this->assertNotEmpty(session('google_oauth_state'));
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        $this->withSession(['google_oauth_state' => 'real-state'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'forged']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_callback_creates_and_logs_in_a_new_user(): void
    {
        $this->seed(PlanSeeder::class);
        $this->fakeGoogle(['sub' => 'g-new', 'email' => 'fresh@gmail.test', 'name' => 'Fresh Face']);

        $this->withSession(['google_oauth_state' => 'st'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'st']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'fresh@gmail.test', 'google_id' => 'g-new']);
        $this->assertNotNull(User::where('email', 'fresh@gmail.test')->value('plan_id'));
    }

    public function test_callback_links_an_existing_account_by_email(): void
    {
        $user = User::factory()->create(['email' => 'existing@gmail.test', 'google_id' => null]);
        $this->fakeGoogle(['sub' => 'g-link', 'email' => 'existing@gmail.test']);

        $this->withSession(['google_oauth_state' => 'st'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'st']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-link', $user->fresh()->google_id);
    }

    public function test_callback_rejects_unverified_google_email(): void
    {
        $this->fakeGoogle(['email' => 'spoof@gmail.test', 'email_verified' => false]);

        $this->withSession(['google_oauth_state' => 'st'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'st']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'spoof@gmail.test']);
    }

    public function test_new_signups_blocked_when_registration_disabled(): void
    {
        Setting::put('allow_registration', '0');
        $this->fakeGoogle(['sub' => 'g-blocked', 'email' => 'blocked@gmail.test']);

        $this->withSession(['google_oauth_state' => 'st'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'st']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'blocked@gmail.test']);
    }

    public function test_suspended_account_cannot_sign_in_with_google(): void
    {
        User::factory()->create(['email' => 'banned@gmail.test', 'google_id' => 'g-ban', 'status' => 'suspended']);
        $this->fakeGoogle(['sub' => 'g-ban', 'email' => 'banned@gmail.test']);

        $this->withSession(['google_oauth_state' => 'st'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'st']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_login_page_shows_google_button_only_when_enabled(): void
    {
        $this->get(route('login'))->assertSee('Continue with Google');

        config(['services.google.enabled' => false]);
        $this->get(route('login'))->assertDontSee('Continue with Google');
    }

    public function test_connect_redirects_an_authenticated_user_to_google(): void
    {
        $user = User::factory()->create(['google_id' => null]);

        $res = $this->actingAs($user)->get(route('account.google.connect'));

        $res->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $res->headers->get('Location'));
        $this->assertSame('connect', session('google_oauth_intent'));
    }

    public function test_connect_links_google_to_the_current_user(): void
    {
        $user = User::factory()->create(['google_id' => null]);
        $this->fakeGoogle(['sub' => 'g-mine', 'email' => 'mine@gmail.test']);

        $this->actingAs($user)
            ->withSession(['google_oauth_state' => 'st', 'google_oauth_intent' => 'connect'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'st']))
            ->assertRedirect(route('account', ['tab' => 'security']))
            ->assertSessionHas('status');

        $this->assertSame('g-mine', $user->fresh()->google_id);
    }

    public function test_connect_rejects_google_already_linked_elsewhere(): void
    {
        User::factory()->create(['google_id' => 'g-taken']);
        $user = User::factory()->create(['google_id' => null]);
        $this->fakeGoogle(['sub' => 'g-taken', 'email' => 'taken@gmail.test']);

        $this->actingAs($user)
            ->withSession(['google_oauth_state' => 'st', 'google_oauth_intent' => 'connect'])
            ->get(route('auth.google.callback', ['code' => 'c', 'state' => 'st']))
            ->assertRedirect(route('account', ['tab' => 'security']))
            ->assertSessionHas('error');

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_user_can_disconnect_google(): void
    {
        $user = User::factory()->create(['google_id' => 'g-bye']); // factory users have a password

        $this->actingAs($user)->delete(route('account.google.disconnect'))
            ->assertRedirect(route('account', ['tab' => 'security']))
            ->assertSessionHas('status');

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_oauth_only_user_cannot_disconnect_without_a_fallback(): void
    {
        $user = User::factory()->create(['google_id' => 'g-locked', 'password' => null]);

        $this->actingAs($user)->delete(route('account.google.disconnect'))
            ->assertRedirect(route('account', ['tab' => 'security']))
            ->assertSessionHas('error');

        $this->assertSame('g-locked', $user->fresh()->google_id);
    }

    public function test_security_tab_reflects_connection_state(): void
    {
        $disconnected = User::factory()->create(['google_id' => null]);
        $this->actingAs($disconnected)->get(route('account', ['tab' => 'security']))
            ->assertOk()->assertSee('Connected accounts')->assertSee('Connect');

        $connected = User::factory()->create(['google_id' => 'g-on']);
        $this->actingAs($connected)->get(route('account', ['tab' => 'security']))
            ->assertOk()->assertSee('Disconnect');
    }

    public function test_admin_can_save_google_login_settings_with_secret_preserved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'section' => 'login',
            'google_login_enabled' => '1',
            'google_client_id' => 'my-client-id',
            'google_client_secret' => 'my-secret',
        ])->assertRedirect();

        $this->assertSame('1', Setting::get('google_login_enabled'));
        $this->assertSame('my-client-id', Setting::get('google_client_id'));
        $this->assertSame('my-secret', Setting::get('google_client_secret'));

        // Re-saving without a secret keeps the stored one.
        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'section' => 'login',
            'google_login_enabled' => '1',
            'google_client_id' => 'my-client-id-2',
        ])->assertRedirect();

        $this->assertSame('my-secret', Setting::get('google_client_secret'));
        $this->assertSame('my-client-id-2', Setting::get('google_client_id'));
    }
}
