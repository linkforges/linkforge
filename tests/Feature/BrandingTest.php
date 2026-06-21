<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\ThemePalette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    /** Appearance save requires a valid preset + font; merge any branding fields on top. */
    private function appearancePayload(array $overrides = []): array
    {
        return array_merge([
            'section' => 'appearance',
            'theme_preset' => ThemePalette::DEFAULT_PRESET,
            'theme_font' => ThemePalette::FONTS[0],
            'theme_scheme' => 'system',
        ], $overrides);
    }

    public function test_admin_can_upload_a_custom_favicon_and_it_overrides_the_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->appearancePayload([
            'favicon_file' => UploadedFile::fake()->image('icon.png', 64, 64),
        ]))->assertRedirect();

        $favicon = Setting::get('brand_favicon');
        $this->assertNotEmpty($favicon);
        $this->assertStringContainsString('uploads/branding/favicon-', $favicon);

        // The favicon partial (used by every layout) now points at the upload.
        $this->get('/')->assertOk()->assertSee($favicon, false);
    }

    public function test_custom_css_and_head_inject_on_the_public_site_but_never_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->appearancePayload([
            'custom_css' => '.lf-brand-test{color:red}',
            'custom_head' => '<meta name="lf-brand-test" content="yes">',
        ]))->assertRedirect();

        $this->get('/')->assertOk()
            ->assertSee('.lf-brand-test{color:red}', false)
            ->assertSee('<meta name="lf-brand-test" content="yes">', false);

        // The admin panel does not include the custom-code partial.
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertDontSee('lf-brand-test', false);
    }

    public function test_footer_text_overrides_the_default_and_expands_the_year_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->appearancePayload([
            'footer_text' => '&copy; {year} Acme Inc.',
        ]))->assertRedirect();

        $this->get('/')->assertOk()->assertSee('&copy; '.date('Y').' Acme Inc.', false);
    }

    public function test_appearance_tab_renders_the_new_branding_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'appearance']))
            ->assertOk()
            ->assertSee('Favicon')
            ->assertSee('Custom code')
            ->assertSee('Custom CSS')
            ->assertSee('Footer');
    }
}
