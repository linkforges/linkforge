<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\LicenseService;
use App\Support\Installer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(); // plans/settings/admin so the install flow has a 'business' plan
        // Simulate a fresh, not-yet-installed upload (the base TestCase marks installed).
        @unlink(Installer::lockPath());
    }

    protected function tearDown(): void
    {
        Installer::markInstalled((string) config('linkforge.version'));
        parent::tearDown();
    }

    public function test_requests_redirect_to_the_installer_when_not_installed(): void
    {
        $this->get('/')->assertRedirect(route('install.welcome'));
        $this->get('/login')->assertRedirect(route('install.welcome'));
    }

    public function test_installer_welcome_renders_requirements(): void
    {
        $this->get(route('install.welcome'))
            ->assertOk()
            ->assertSee('Requirements')
            ->assertSee('Writable paths');
    }

    public function test_installer_is_sealed_once_installed(): void
    {
        Installer::markInstalled('1.0.0');

        $this->get(route('install.welcome'))->assertRedirect('/');
        $this->get(route('install.database'))->assertRedirect('/');
    }

    public function test_database_step_validates_input(): void
    {
        $this->post(route('install.database.save'), [])
            ->assertSessionHasErrors(['site_name', 'app_url', 'db_host', 'db_database', 'db_username']);
    }

    public function test_account_step_requires_database_step_first(): void
    {
        $this->get(route('install.account'))->assertRedirect(route('install.database'));
        $this->get(route('install.license'))->assertRedirect(route('install.account'));
    }

    public function test_admin_account_then_license_then_finish(): void
    {
        $this->withSession(['install.db' => true]);

        $this->post(route('install.account.save'), [
            'name' => 'Site Owner',
            'email' => 'owner@example.com',
            'password' => 'supersecret',
            'password_confirmation' => 'supersecret',
        ])->assertRedirect(route('install.license'));

        $this->assertDatabaseHas('users', ['email' => 'owner@example.com', 'role' => 'admin', 'status' => 'active']);

        // A license is now required: an empty code is rejected and install is not sealed.
        $this->post(route('install.license.save'), ['purchase_code' => ''])
            ->assertSessionHasErrors('purchase_code');
        $this->assertFalse(Installer::isInstalled());

        // A valid purchase code (verified via the relay) completes the install.
        Http::fake(['*/verify' => Http::response(['valid' => true, 'license' => ['domain' => 'example.com']])]);
        $this->post(route('install.license.save'), ['purchase_code' => '8f3c9d21-1a2b-4c5d-9e8f-0a1b2c3d4e5f'])
            ->assertRedirect(route('install.complete'));

        $this->get(route('install.complete'))->assertOk()->assertSee('installed');

        $this->assertTrue(Installer::isInstalled());
    }

    public function test_license_service_rejects_a_malformed_code(): void
    {
        $result = app(LicenseService::class)->verify('not-a-code');
        $this->assertFalse($result['valid']);
    }

    public function test_license_service_fails_open_without_a_relay(): void
    {
        config(['linkforge.license.relay_url' => '']);

        $result = app(LicenseService::class)->verify('8f3c9d21-1a2b-4c5d-9e8f-0a1b2c3d4e5f');

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['unverified'] ?? false);
    }

    public function test_license_service_verifies_against_the_relay(): void
    {
        // A "valid" verdict is only trusted as confirmed when it carries a signature the
        // app's baked public key verifies (otherwise it's stored as merely "unverified").
        $kp = sodium_crypto_sign_keypair();
        config([
            'linkforge.license.relay_url' => 'https://relay.test',
            'linkforge.license.verify_public_key' => base64_encode(sodium_crypto_sign_publickey($kp)),
        ]);
        $code = '8f3c9d21-1a2b-4c5d-9e8f-0a1b2c3d4e5f';
        $domain = 'buyer.test';
        $issuedAt = gmdate('c');
        $sig = base64_encode(sodium_crypto_sign_detached(
            'lf-license-v1|'.$code.'|'.$domain.'|valid|'.$issuedAt,
            sodium_crypto_sign_secretkey($kp)
        ));
        Http::fake([
            'relay.test/verify' => Http::response([
                'valid' => true, 'license' => ['buyer' => 'bob', 'item_id' => '123'], 'issued_at' => $issuedAt, 'signature' => $sig,
            ], 200),
        ]);

        $result = app(LicenseService::class)->verify($code, $domain);

        $this->assertTrue($result['valid']);
        $this->assertFalse($result['unverified'] ?? false);
        $this->assertSame('bob', $result['license']['buyer'] ?? null);
    }

    public function test_license_service_hard_fails_on_a_relay_rejection(): void
    {
        config(['linkforge.license.relay_url' => 'https://relay.test']);
        Http::fake([
            'relay.test/verify' => Http::response(['valid' => false, 'message' => 'Purchase code not found.'], 422),
        ]);

        $result = app(LicenseService::class)->verify('8f3c9d21-1a2b-4c5d-9e8f-0a1b2c3d4e5f');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function test_license_service_fails_open_on_relay_error(): void
    {
        config(['linkforge.license.relay_url' => 'https://relay.test']);
        Http::fake([
            'relay.test/verify' => Http::response('upstream down', 502),
        ]);

        $result = app(LicenseService::class)->verify('8f3c9d21-1a2b-4c5d-9e8f-0a1b2c3d4e5f');

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['unverified'] ?? false);
    }

    public function test_license_service_stores_status(): void
    {
        $svc = app(LicenseService::class);
        $code = '8f3c9d21-1a2b-4c5d-9e8f-0a1b2c3d4e5f';
        $svc->store($code, ['valid' => true, 'unverified' => true, 'message' => 'ok']);

        $this->assertSame($code, Setting::get('license_code'));
        $this->assertSame('unverified', Setting::get('license_status'));
    }

    public function test_write_env_updates_and_appends_keys(): void
    {
        $dir = sys_get_temp_dir();
        $file = 'lf_env_'.uniqid().'.env';
        file_put_contents($dir.DIRECTORY_SEPARATOR.$file, "APP_NAME=Old\nFOO=bar\n");

        $this->app->useEnvironmentPath($dir);
        $this->app->loadEnvironmentFrom($file);

        Installer::writeEnv(['APP_NAME' => 'New Name', 'DB_HOST' => '127.0.0.1']);

        $out = (string) file_get_contents($dir.DIRECTORY_SEPARATOR.$file);
        $this->assertStringContainsString('APP_NAME="New Name"', $out);
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $out);
        $this->assertStringContainsString('FOO=bar', $out); // untouched key preserved

        @unlink($dir.DIRECTORY_SEPARATOR.$file);
    }
}
