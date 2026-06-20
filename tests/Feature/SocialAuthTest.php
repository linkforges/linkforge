<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(PlanSeeder::class);
        config([
            'services.github.enabled' => true,
            'services.github.client_id' => 'Iv1.github',
            'services.github.client_secret' => 'gh-secret',
            'services.facebook.enabled' => true,
            'services.facebook.client_id' => '123456',
            'services.facebook.client_secret' => 'fb-secret',
        ]);
    }

    // ---------------- GitHub ----------------

    public function test_github_redirect_sends_user_with_state(): void
    {
        $res = $this->get(route('auth.github.redirect'));

        $res->assertRedirect();
        $location = urldecode($res->headers->get('Location'));
        $this->assertStringContainsString('github.com/login/oauth/authorize', $location);
        $this->assertStringContainsString('client_id=Iv1.github', $location);
        $this->assertStringContainsString('scope=read:user user:email', $location);
        $this->assertNotEmpty(session('github_oauth_state'));
    }

    public function test_github_routes_404_when_disabled(): void
    {
        config(['services.github.enabled' => false]);
        $this->get(route('auth.github.redirect'))->assertNotFound();
    }

    public function test_github_callback_signs_in_and_creates_user(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(['access_token' => 'gho_token']),
            'api.github.com/user' => Http::response(['id' => 4242, 'login' => 'octocat', 'name' => 'Octo Cat', 'email' => 'octo@github.test', 'avatar_url' => 'https://x/y.png']),
        ]);

        $this->withSession(['github_oauth_state' => 's'])
            ->get(route('auth.github.callback', ['code' => 'c', 'state' => 's']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['github_id' => '4242', 'email' => 'octo@github.test']);
    }

    public function test_github_falls_back_to_primary_verified_email_when_private(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(['access_token' => 'gho_token']),
            'api.github.com/user/emails' => Http::response([
                ['email' => 'secondary@github.test', 'primary' => false, 'verified' => true],
                ['email' => 'primary@github.test', 'primary' => true, 'verified' => true],
            ]),
            'api.github.com/user' => Http::response(['id' => 99, 'login' => 'priv', 'name' => null, 'email' => null]),
        ]);

        $this->withSession(['github_oauth_state' => 's'])
            ->get(route('auth.github.callback', ['code' => 'c', 'state' => 's']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['github_id' => '99', 'email' => 'primary@github.test']);
    }

    // ---------------- Facebook ----------------

    public function test_facebook_redirect_sends_user_with_state(): void
    {
        $res = $this->get(route('auth.facebook.redirect'));

        $res->assertRedirect();
        $location = urldecode($res->headers->get('Location'));
        $this->assertStringContainsString('facebook.com/v19.0/dialog/oauth', $location);
        $this->assertStringContainsString('client_id=123456', $location);
        $this->assertNotEmpty(session('facebook_oauth_state'));
    }

    public function test_facebook_callback_signs_in_and_creates_user(): void
    {
        Http::fake([
            'graph.facebook.com/*/oauth/access_token' => Http::response(['access_token' => 'fb_token']),
            'graph.facebook.com/*/me*' => Http::response(['id' => 'fb-555', 'name' => 'Zuck', 'email' => 'zuck@fb.test']),
        ]);

        $this->withSession(['facebook_oauth_state' => 's'])
            ->get(route('auth.facebook.callback', ['code' => 'c', 'state' => 's']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['facebook_id' => 'fb-555', 'email' => 'zuck@fb.test']);
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        $this->withSession(['github_oauth_state' => 'real'])
            ->get(route('auth.github.callback', ['code' => 'c', 'state' => 'forged']))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // ---------------- Connect / disconnect ----------------

    public function test_user_can_connect_and_disconnect_github(): void
    {
        Http::fake([
            'github.com/login/oauth/access_token' => Http::response(['access_token' => 'gho_token']),
            'api.github.com/user' => Http::response(['id' => 7, 'login' => 'me', 'name' => 'Me', 'email' => 'me@github.test']),
        ]);

        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->actingAs($user)->withSession(['github_oauth_state' => 's', 'github_oauth_intent' => 'connect'])
            ->get(route('auth.github.callback', ['code' => 'c', 'state' => 's']))
            ->assertRedirect(route('account', ['tab' => 'security']));
        $this->assertSame('7', $user->fresh()->github_id);

        $this->actingAs($user)->delete(route('account.github.disconnect'))->assertRedirect();
        $this->assertNull($user->fresh()->github_id);
    }

    public function test_login_page_shows_enabled_provider_buttons(): void
    {
        $this->get(route('login'))
            ->assertSee('Continue with GitHub')
            ->assertSee('Continue with Facebook');
    }

    public function test_admin_can_save_all_provider_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'section' => 'login',
            'github_login_enabled' => '1',
            'github_client_id' => 'Iv1.saved',
            'github_client_secret' => 'shhh',
            'facebook_login_enabled' => '1',
            'facebook_client_id' => '999',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'github_client_id', 'value' => 'Iv1.saved']);
        $this->assertDatabaseHas('settings', ['key' => 'facebook_client_id', 'value' => '999']);
    }
}
