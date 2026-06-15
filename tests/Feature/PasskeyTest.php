<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Create a stored passkey row for a user without running a real ceremony. */
    private function makePasskey(User $user, string $name = 'My key'): void
    {
        $user->passkeys()->create([
            'name' => $name,
            'credential_id' => 'cred-'.Str::random(20),
            'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
        ]);
    }

    public function test_user_is_a_passkey_user(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(PasskeyUser::class, $user);
        $this->assertFalse($user->hasPasskeysEnabled());

        $this->makePasskey($user);
        $this->assertTrue($user->fresh()->hasPasskeysEnabled());
    }

    public function test_security_tab_shows_passkeys_card(): void
    {
        $user = User::factory()->create();
        $this->makePasskey($user, 'Work laptop');

        $this->actingAs($user)->get(route('account', ['tab' => 'security']))
            ->assertOk()
            ->assertSee('Passkeys')
            ->assertSee('Add passkey')
            ->assertSee('Work laptop');
    }

    public function test_registration_options_require_password_confirmation(): void
    {
        $user = User::factory()->create();

        // No recent password confirmation -> Fortify/passkeys answer JSON with 423.
        $this->actingAs($user)
            ->getJson('/user/passkeys/options')
            ->assertStatus(423);
    }

    public function test_registration_options_returned_once_password_confirmed(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/user/passkeys/options')
            ->assertOk();

        $options = $res->json('options');
        $this->assertNotEmpty($options['challenge']);
        $this->assertNotEmpty($options['user']['id']);
        $this->assertArrayHasKey('rp', $options);
        $this->assertSame($user->email, $options['user']['name']);
    }

    public function test_user_can_delete_their_passkey(): void
    {
        $user = User::factory()->create();
        $this->makePasskey($user);
        $passkey = $user->passkeys()->firstOrFail();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->deleteJson('/user/passkeys/'.$passkey->id)
            ->assertOk();

        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
    }

    public function test_user_cannot_delete_another_users_passkey(): void
    {
        $owner = User::factory()->create();
        $this->makePasskey($owner);
        $passkey = $owner->passkeys()->firstOrFail();

        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->deleteJson('/user/passkeys/'.$passkey->id)
            ->assertForbidden();

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    public function test_login_options_are_available_to_guests(): void
    {
        $res = $this->getJson('/passkeys/login/options')->assertOk();

        $this->assertNotEmpty($res->json('options.challenge'));
    }

    public function test_login_page_offers_passkey_sign_in(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in with a passkey');
    }
}
