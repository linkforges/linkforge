<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_published_page_renders_and_draft_is_hidden(): void
    {
        Page::create(['title' => 'Terms', 'slug' => 'terms', 'body' => 'Our **terms** here.', 'status' => 'published']);
        Page::create(['title' => 'Secret', 'slug' => 'secret', 'body' => 'Hidden.', 'status' => 'draft']);

        $this->get('/page/terms')->assertOk()->assertSee('Terms')->assertSee('<strong>terms</strong>', false);
        $this->get('/page/secret')->assertNotFound();
        $this->get('/page/does-not-exist')->assertNotFound();
    }

    public function test_sitemap_lists_published_content(): void
    {
        Page::create(['title' => 'Privacy', 'slug' => 'privacy', 'body' => 'x', 'status' => 'published']);

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<urlset', false)
            ->assertSee('/page/privacy', false);
    }

    public function test_robots_txt_is_served_dynamically(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap:');
    }

    public function test_seeded_pages_appear_in_the_footer(): void
    {
        (new PageSeeder)->run();

        $this->get('/')->assertOk()->assertSee('Terms of Service');
    }

    public function test_cookie_consent_shows_when_enabled(): void
    {
        Setting::putMany(['cookie_consent_enabled' => '1', 'cookie_consent_text' => 'We use cookies here.']);

        $this->get('/')->assertOk()->assertSee('We use cookies here', false);
    }

    public function test_admin_can_create_a_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'Custom Page', 'body' => 'Hello', 'status' => 'published', 'show_in_footer' => '1',
        ])->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('pages', ['slug' => 'custom-page', 'status' => 'published']);
    }

    public function test_creating_pages_requires_an_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.pages.index'))
            ->assertForbidden();
    }
}
