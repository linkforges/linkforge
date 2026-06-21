<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeepLinkTest extends TestCase
{
    use RefreshDatabase;

    private const IOS_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148';

    private const ANDROID_UA = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Mobile Safari/537.36';

    private const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    private function proUser(): User
    {
        return User::factory()->create(['plan_id' => Plan::where('slug', 'pro')->value('id')]);
    }

    private function freeUser(): User
    {
        return User::factory()->create(['plan_id' => Plan::where('slug', 'free')->value('id')]);
    }

    private function deepLink(User $user, array $deep, string $alias = 'app'): Link
    {
        return Link::create([
            'user_id' => $user->id,
            'domain_id' => Domain::where('is_default', true)->value('id'),
            'alias' => $alias,
            'long_url' => 'https://example.com/web',
            'type' => 'direct',
            'safety_status' => 'safe',
            'meta' => ['deep_link' => $deep],
        ]);
    }

    public function test_ios_visitor_gets_the_app_deeplink_page(): void
    {
        $this->deepLink($this->proUser(), ['ios' => 'myapp://item/1', 'android' => 'myapp://item/1a']);

        $this->get('/app', ['User-Agent' => self::IOS_UA])
            ->assertOk()
            ->assertSee('myapp://item/1', false)           // the iOS URI is attempted
            ->assertSee('https://example.com/web', false); // web fallback is present
    }

    public function test_android_visitor_gets_the_android_uri(): void
    {
        $this->deepLink($this->proUser(), ['ios' => 'myapp://ios', 'android' => 'myapp://android']);

        $this->get('/app', ['User-Agent' => self::ANDROID_UA])->assertOk()->assertSee('myapp://android', false);
    }

    public function test_desktop_visitor_redirects_normally(): void
    {
        $this->deepLink($this->proUser(), ['ios' => 'myapp://ios']);

        $this->get('/app', ['User-Agent' => self::DESKTOP_UA])->assertRedirect('https://example.com/web');
    }

    public function test_deeplink_does_not_fire_without_the_plan_feature(): void
    {
        // Even with meta set, a plan that lacks deep links ignores it (e.g. after a downgrade).
        $this->deepLink($this->freeUser(), ['ios' => 'myapp://ios']);

        $this->get('/app', ['User-Agent' => self::IOS_UA])->assertRedirect('https://example.com/web');
    }

    public function test_pro_user_can_save_deep_links(): void
    {
        $user = $this->proUser();

        $this->actingAs($user)->post('/links', [
            'long_url' => 'https://example.com', 'alias' => 'dl',
            'deep_link_ios' => 'myapp://x', 'deep_link_android' => 'myapp://y',
        ])->assertRedirect(route('links.index'));

        $link = $user->links()->where('alias', 'dl')->firstOrFail();
        $this->assertSame('myapp://x', data_get($link->meta, 'deep_link.ios'));
        $this->assertSame('myapp://y', data_get($link->meta, 'deep_link.android'));
    }

    public function test_script_scheme_deep_links_are_rejected(): void
    {
        $user = $this->proUser();

        // javascript: would execute when the deep link is opened (window.location on the view).
        $this->actingAs($user)->post('/links', [
            'long_url' => 'https://example.com', 'alias' => 'xss',
            'deep_link_ios' => 'javascript:alert(document.cookie)',
        ])->assertSessionHasErrors('deep_link_ios');
        $this->assertDatabaseMissing('links', ['alias' => 'xss']);

        // Whitespace / control-char obfuscation (java\tscript:) is also rejected.
        $this->actingAs($user)->post('/links', [
            'long_url' => 'https://example.com', 'alias' => 'xss2',
            'deep_link_android' => "java\tscript:alert(1)",
        ])->assertSessionHasErrors('deep_link_android');
        $this->assertDatabaseMissing('links', ['alias' => 'xss2']);

        // A legitimate custom app scheme still saves.
        $this->actingAs($user)->post('/links', [
            'long_url' => 'https://example.com', 'alias' => 'ok',
            'deep_link_ios' => 'myapp://item/1',
        ])->assertRedirect(route('links.index'));
        $this->assertSame('myapp://item/1', data_get($user->links()->where('alias', 'ok')->first()->meta, 'deep_link.ios'));
    }

    public function test_rule_target_urls_reject_script_schemes(): void
    {
        $user = $this->proUser();

        // FILTER_VALIDATE_URL passes "javascript://…", which executes in window.location on the
        // splash view. The safe https rule must persist; the script one must be dropped.
        $this->actingAs($user)->post('/links', [
            'long_url' => 'https://example.com', 'alias' => 'ruled',
            'rules' => [
                ['type' => 'geo', 'match' => 'US', 'target_url' => 'javascript://%0aalert(document.cookie)'],
                ['type' => 'geo', 'match' => 'GB', 'target_url' => 'https://safe.example.com'],
            ],
        ])->assertRedirect(route('links.index'));

        $targets = $user->links()->where('alias', 'ruled')->firstOrFail()->rules()->pluck('target_url')->all();
        $this->assertContains('https://safe.example.com', $targets);
        $this->assertNotContains('javascript://%0aalert(document.cookie)', $targets);
    }

    public function test_free_user_cannot_save_deep_links(): void
    {
        $user = $this->freeUser();

        $this->actingAs($user)->post('/links', [
            'long_url' => 'https://example.com', 'alias' => 'dl2', 'deep_link_ios' => 'myapp://x',
        ]);

        $this->assertNull(data_get($user->links()->where('alias', 'dl2')->first()?->meta, 'deep_link.ios'));
    }

    public function test_form_shows_deeplink_fields_only_for_eligible_plans(): void
    {
        $this->actingAs($this->proUser())->get(route('links.create'))->assertSee('Mobile deep links');
        $this->actingAs($this->freeUser())->get(route('links.create'))->assertDontSee('Mobile deep links');
    }
}
