<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_available_locales_include_shipped_json_files(): void
    {
        $available = Locales::available();
        $this->assertArrayHasKey('en', $available);
        $this->assertArrayHasKey('es', $available);
        $this->assertSame('Spanish', $available['es']);
    }

    public function test_strings_translate_and_fall_back_to_english(): void
    {
        App::setLocale('es');
        $this->assertSame('Bienvenido de nuevo', __('Welcome back'));
        $this->assertSame('Panel', __('Dashboard'));
        $this->assertSame('A string with no translation', __('A string with no translation')); // key fallback

        App::setLocale('en');
        $this->assertSame('Welcome back', __('Welcome back'));
    }

    public function test_rtl_detection(): void
    {
        $this->assertTrue(Locales::isRtl('ar'));
        $this->assertTrue(Locales::isRtl('he'));
        $this->assertFalse(Locales::isRtl('en'));
        $this->assertFalse(Locales::isRtl('es'));
    }

    public function test_login_page_renders_in_spanish_via_cookie(): void
    {
        $this->withUnencryptedCookie('lf_locale', 'es')->get('/login')
            ->assertOk()
            ->assertSee('Bienvenido de nuevo')   // Welcome back
            ->assertSee('Iniciar sesión')        // Sign in
            ->assertSee('Correo electrónico');   // Email address
    }

    public function test_authenticated_nav_translates_for_user_locale(): void
    {
        $user = User::factory()->create(['settings' => ['locale' => 'es']]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Enlaces')      // Links
            ->assertSee('Analíticas')   // Analytics
            ->assertSee('Cerrar sesión'); // Log out
    }

    public function test_switch_persists_user_choice_and_sets_cookie(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('locale.switch', 'es'))
            ->assertRedirect()
            ->assertCookie('lf_locale', 'es', false); // not encrypted (excepted)

        $this->assertSame('es', data_get($user->fresh()->settings, 'locale'));
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $this->get(route('locale.switch', 'zz'))->assertNotFound();
    }
}
