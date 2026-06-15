<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @var list<string> avatar files created during a test, cleaned up after. */
    private array $createdAvatars = [];

    protected function tearDown(): void
    {
        foreach ($this->createdAvatars as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    public function test_account_page_renders_both_tabs(): void
    {
        $user = User::factory()->create(['email' => 'jane@test.test']);

        $this->actingAs($user)->get(route('account'))
            ->assertOk()->assertSee('Profile')->assertSee('Security')->assertSee('jane@test.test');

        $this->actingAs($user)->get(route('account', ['tab' => 'security']))
            ->assertOk()->assertSee('Change password')->assertSee('Two-factor authentication');
    }

    public function test_account_page_requires_auth(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile'), [
            'name' => 'New Name',
            'email' => 'new@test.test',
        ])->assertRedirect(route('account', ['tab' => 'profile']))->assertSessionHas('status');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'new@test.test']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@test.test']);
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile'), [
            'name' => 'X',
            'email' => 'taken@test.test',
        ])->assertSessionHasErrors('email');
    }

    public function test_user_can_upload_and_remove_an_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('me.jpg', 400, 400),
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->createdAvatars[] = public_path('uploads/avatars/'.$user->avatar);
        $this->assertFileExists(public_path('uploads/avatars/'.$user->avatar));

        // Remove it.
        $this->actingAs($user)->put(route('account.profile'), [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => '1',
        ])->assertRedirect();

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)->put(route('account.password'), [
            'current_password' => 'wrong-password',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)->put(route('account.password'), [
            'current_password' => 'password',
            'password' => 'a-much-better-pass',
            'password_confirmation' => 'a-much-better-pass',
        ])->assertRedirect(route('account', ['tab' => 'security']))->assertSessionHas('status');

        $this->assertTrue(Hash::check('a-much-better-pass', $user->fresh()->password));
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'nope'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $token = $user->createToken('cli'); // a Sanctum API token

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        // API tokens are revoked, not left orphaned.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_last_admin_cannot_delete_their_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('password')]);

        $this->actingAs($admin)->delete(route('account.destroy'), ['password' => 'password'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_when_another_admin_exists(): void
    {
        User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('password')]);

        $this->actingAs($admin)->delete(route('account.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    public function test_two_factor_can_be_enabled_via_fortify_endpoint(): void
    {
        $user = User::factory()->create();

        // Bypass Fortify's "confirm password" gate for this request.
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication')
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_security_tab_reflects_enabled_two_factor(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('SECRETKEY'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user)->get(route('account', ['tab' => 'security']))
            ->assertOk()->assertSee('Enabled')->assertSee('Regenerate codes');
    }
}
