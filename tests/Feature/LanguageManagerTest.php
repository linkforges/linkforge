<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageManagerTest extends TestCase
{
    use RefreshDatabase;

    /** Throwaway locale codes created during tests; removed in tearDown. */
    private array $tempCodes = ['zz', 'zy'];

    private ?string $enBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        // Protect the shipped en.json — the rescan test regenerates it.
        $this->enBackup = is_file(lang_path('en.json')) ? file_get_contents(lang_path('en.json')) : null;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempCodes as $code) {
            if (is_file(lang_path($code.'.json'))) {
                @unlink(lang_path($code.'.json'));
            }
        }
        if ($this->enBackup !== null) {
            file_put_contents(lang_path('en.json'), $this->enBackup);
        }
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_index_lists_installed_locales_for_admin(): void
    {
        $this->actingAs($this->admin())->get(route('admin.languages'))
            ->assertOk()
            ->assertSee('Installed languages')
            ->assertSee('English')
            ->assertSee('Spanish');
    }

    public function test_non_admin_cannot_access(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user', 'status' => 'active']))
            ->get(route('admin.languages'))
            ->assertForbidden();
    }

    public function test_adding_a_locale_creates_an_empty_json_file(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.languages.store'), ['code' => 'ZZ'])
            ->assertRedirect(route('admin.languages.edit', 'zz'));

        $this->assertFileExists(lang_path('zz.json'));
        $this->assertSame([], json_decode((string) file_get_contents(lang_path('zz.json')), true));
    }

    public function test_invalid_and_duplicate_codes_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'not a code'])
            ->assertSessionHas('error');
        $this->assertFileDoesNotExist(lang_path('not a code.json'));

        // English is the source and cannot be added.
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'en'])
            ->assertSessionHas('error');

        // Spanish already ships.
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'es'])
            ->assertSessionHas('error');
    }

    public function test_saving_translations_writes_only_non_empty_values(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'zz']);

        $this->actingAs($admin)->put(route('admin.languages.update', 'zz'), [
            't' => [
                'Dashboard' => 'Tablero',
                'Links' => '   ',          // whitespace only — should be dropped
                'Analytics' => 'Analítica',
            ],
        ])->assertRedirect(route('admin.languages.edit', 'zz'));

        $stored = json_decode((string) file_get_contents(lang_path('zz.json')), true);
        $this->assertSame('Tablero', $stored['Dashboard']);
        $this->assertSame('Analítica', $stored['Analytics']);
        $this->assertArrayNotHasKey('Links', $stored); // blank omitted so it falls back to English
    }

    public function test_update_ignores_keys_not_in_the_source(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'zz']);

        $this->actingAs($admin)->put(route('admin.languages.update', 'zz'), [
            't' => [
                'Dashboard' => 'Tablero',
                'Some bogus key that is not in en.json' => 'junk',
            ],
        ]);

        $stored = json_decode((string) file_get_contents(lang_path('zz.json')), true);
        $this->assertArrayHasKey('Dashboard', $stored);
        $this->assertArrayNotHasKey('Some bogus key that is not in en.json', $stored);
    }

    public function test_import_merges_matching_keys_and_ignores_the_rest(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'zz']);

        $this->actingAs($admin)->post(route('admin.languages.import', 'zz'), [
            'json' => json_encode([
                'Dashboard' => 'Tablero',
                'Links' => '',                 // blank — ignored
                'Totally unknown key' => 'x',  // not a source key — ignored
            ]),
        ])->assertRedirect(route('admin.languages.edit', 'zz'));

        $stored = json_decode((string) file_get_contents(lang_path('zz.json')), true);
        $this->assertSame('Tablero', $stored['Dashboard']);
        $this->assertArrayNotHasKey('Links', $stored);
        $this->assertArrayNotHasKey('Totally unknown key', $stored);
    }

    public function test_import_rejects_non_json(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'zz']);

        $this->actingAs($admin)->post(route('admin.languages.import', 'zz'), ['json' => 'not json'])
            ->assertSessionHas('error');
    }

    public function test_export_downloads_the_locale_json(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'zz']);
        $this->actingAs($admin)->put(route('admin.languages.update', 'zz'), ['t' => ['Dashboard' => 'Tablero']]);

        $res = $this->actingAs($admin)->get(route('admin.languages.export', 'zz'));
        $res->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="zz.json"');
        $this->assertStringContainsString('Tablero', $res->getContent());
    }

    public function test_setting_default_locale_persists(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.languages.default'), ['default_locale' => 'es'])
            ->assertRedirect(route('admin.languages'));

        $this->assertSame('es', Setting::get('default_locale'));
    }

    public function test_setting_an_uninstalled_default_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.languages.default'), ['default_locale' => 'zz'])
            ->assertSessionHasErrors('default_locale');
    }

    public function test_deleting_a_locale_removes_the_file_and_resets_default(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.languages.store'), ['code' => 'zz']);
        Setting::put('default_locale', 'zz');

        $this->actingAs($admin)->delete(route('admin.languages.destroy', 'zz'))
            ->assertRedirect(route('admin.languages'));

        $this->assertFileDoesNotExist(lang_path('zz.json'));
        $this->assertSame('en', Setting::get('default_locale')); // fell back off the deleted locale
    }

    public function test_english_source_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin())->delete(route('admin.languages.destroy', 'en'))
            ->assertNotFound();
        $this->assertFileExists(lang_path('en.json'));
    }

    public function test_rescan_refreshes_the_english_key_list(): void
    {
        $this->actingAs($this->admin())->post(route('admin.languages.scan'))
            ->assertRedirect(route('admin.languages'))
            ->assertSessionHas('status');

        // en.json is valid JSON of key => value identity pairs after a scan.
        $en = json_decode((string) file_get_contents(lang_path('en.json')), true);
        $this->assertIsArray($en);
        $this->assertArrayHasKey('Dashboard', $en);
        $this->assertSame('Dashboard', $en['Dashboard']);
    }
}
