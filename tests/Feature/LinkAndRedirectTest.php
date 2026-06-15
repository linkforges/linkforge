<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LinkAndRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(); // plans, settings, default domain ("localhost"), admin
    }

    private function defaultDomain(): Domain
    {
        return Domain::where('is_default', true)->firstOrFail();
    }

    private function makeLink(array $attrs = []): Link
    {
        $user = User::factory()->create();

        return Link::create(array_merge([
            'user_id' => $user->id,
            'domain_id' => $this->defaultDomain()->id,
            'alias' => 'go',
            'long_url' => 'https://example.com/dest',
            'type' => 'direct',
            'safety_status' => 'safe',
        ], $attrs));
    }

    public function test_user_can_create_a_link_with_custom_alias(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/links', ['long_url' => 'https://example.com/page', 'alias' => 'promo'])
            ->assertRedirect(route('links.index'));

        $this->assertDatabaseHas('links', [
            'alias' => 'promo',
            'user_id' => $user->id,
            'long_url' => 'https://example.com/page',
        ]);
    }

    public function test_blank_alias_is_auto_generated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/links', ['long_url' => 'https://example.com'])
            ->assertRedirect(route('links.index'));

        $link = $user->links()->firstOrFail();
        $this->assertNotEmpty($link->alias);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $link->alias);
    }

    public function test_reserved_alias_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/links', ['long_url' => 'https://example.com', 'alias' => 'admin'])
            ->assertSessionHasErrors('alias');

        $this->assertDatabaseMissing('links', ['alias' => 'admin']);
    }

    public function test_redirect_resolves_and_records_a_click(): void
    {
        $link = $this->makeLink(['alias' => 'go', 'long_url' => 'https://example.com/dest']);

        $this->get('/go')->assertRedirect('https://example.com/dest');

        $this->assertDatabaseHas('clicks', ['link_id' => $link->id]);
        $this->assertSame(1, $link->fresh()->clicks);
    }

    public function test_unknown_alias_returns_404(): void
    {
        $this->get('/this-does-not-exist')->assertNotFound();
    }

    public function test_inactive_link_shows_unavailable(): void
    {
        $this->makeLink(['alias' => 'off', 'is_active' => false]);

        $this->get('/off')->assertStatus(410)->assertSee('Link unavailable');
    }

    public function test_password_protected_link_prompts_then_unlocks(): void
    {
        $this->makeLink([
            'alias' => 'secret',
            'long_url' => 'https://example.com/vip',
            'password' => Hash::make('letmein'),
        ]);

        $this->get('/secret')->assertOk()->assertSee('Password required');

        $this->post('/unlock/secret', ['password' => 'letmein'])->assertRedirect('/secret');
        $this->post('/unlock/secret', ['password' => 'wrong'])->assertStatus(422);
    }

    public function test_owner_only_can_edit_a_link(): void
    {
        $link = $this->makeLink(['alias' => 'mine']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('links.edit', $link))->assertForbidden();
    }

    /** Guards against N+1 / lazy-load 500s that strict mode triggers in production. */
    public function test_pages_render_with_links_under_strict_lazy_loading(): void
    {
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(true);

        try {
            $plan = \App\Models\Plan::where('slug', 'free')->first();
            $user = User::factory()->create(['plan_id' => $plan->id]);

            $this->actingAs($user)->post('/links', ['long_url' => 'https://example.com/a'])
                ->assertRedirect(route('links.index'));

            $this->actingAs($user)->get('/links')->assertOk();
            $this->actingAs($user)->get('/dashboard')->assertOk();
        } finally {
            \Illuminate\Database\Eloquent\Model::preventLazyLoading(false);
        }
    }

    public function test_link_appends_utm_and_custom_params_on_redirect(): void
    {
        $link = $this->makeLink([
            'alias' => 'utm',
            'long_url' => 'https://example.com/page?ref=keep',
            'params' => ['utm_source' => 'newsletter', 'utm_medium' => 'email', 'aff' => 'abc123'],
        ]);

        $loc = $this->get('/utm')->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('utm_source=newsletter', $loc);
        $this->assertStringContainsString('utm_medium=email', $loc);
        $this->assertStringContainsString('aff=abc123', $loc);
        $this->assertStringContainsString('ref=keep', $loc); // existing query param preserved
    }

    public function test_creating_a_link_parses_utm_and_custom_params(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/links', [
            'long_url' => 'https://example.com',
            'utm_source' => 'fb',
            'utm_campaign' => 'spring',
            'custom_params' => "ref=partner\naff = x1\nignored-line",
        ])->assertRedirect(route('links.index'));

        $this->assertSame(
            ['utm_source' => 'fb', 'utm_campaign' => 'spring', 'ref' => 'partner', 'aff' => 'x1'],
            $user->links()->firstOrFail()->params,
        );
    }

    public function test_link_resolves_on_a_verified_custom_domain(): void
    {
        $user = User::factory()->create();
        $domain = $user->domains()->create(['host' => 'go.brand.test', 'status' => 'active', 'is_default' => false]);
        Link::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'alias' => 'promo',
            'long_url' => 'https://example.com/promo',
            'type' => 'direct',
            'safety_status' => 'safe',
        ]);

        $this->get('http://go.brand.test/promo')->assertRedirect('https://example.com/promo');
    }

    public function test_custom_domain_does_not_serve_links_from_another_domain(): void
    {
        // 'only-default' exists only on the default (localhost) domain.
        $this->makeLink(['alias' => 'only-default']);

        $user = User::factory()->create();
        $user->domains()->create(['host' => 'go.brand.test', 'status' => 'active', 'is_default' => false]);

        $this->get('http://go.brand.test/only-default')->assertNotFound();
    }
}
