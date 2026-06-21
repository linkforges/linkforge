<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Support\Dates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SettingsPolishTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_dates_helper_uses_the_configured_format(): void
    {
        config(['linkforge.date_format' => 'Y-m-d']);
        $this->assertSame('2026-01-05', Dates::format('2026-01-05 10:30'));
    }

    public function test_saving_general_persists_localization_and_signup_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::first();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'section' => 'general',
            'site_name' => 'LinkForge',
            'app_timezone' => 'Europe/London',
            'date_format' => 'd/m/Y',
            'signup_blocked_domains' => "spam.test\nbad.example",
            'signup_default_plan' => $plan->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'app_timezone', 'value' => 'Europe/London']);
        $this->assertDatabaseHas('settings', ['key' => 'date_format', 'value' => 'd/m/Y']);
        $this->assertDatabaseHas('settings', ['key' => 'signup_default_plan', 'value' => (string) $plan->id]);
    }

    public function test_admin_can_bulk_suspend_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $a = User::factory()->create(['status' => 'active']);
        $b = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'action' => 'suspend', 'ids' => [$a->id, $b->id],
        ])->assertRedirect();

        $this->assertSame('suspended', $a->fresh()->status);
        $this->assertSame('suspended', $b->fresh()->status);
    }

    public function test_bulk_never_touches_self_or_other_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'action' => 'suspend', 'ids' => [$admin->id, $otherAdmin->id],
        ])->assertRedirect();

        $this->assertSame('active', $admin->fresh()->status);
        $this->assertSame('active', $otherAdmin->fresh()->status);
    }

    public function test_bulk_requires_an_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.users.bulk'), ['action' => 'suspend', 'ids' => [1]])
            ->assertForbidden();
    }

    public function test_blocked_email_domain_rejects_signup(): void
    {
        Mail::fake();
        Setting::putMany(['signup_blocked_domains' => 'blocked.test']);

        $this->expectException(ValidationException::class);
        app(CreateNewUser::class)->create([
            'name' => 'Spammer', 'email' => 'x@blocked.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
    }

    public function test_default_signup_plan_is_applied(): void
    {
        Mail::fake();
        $plan = Plan::where('slug', '!=', 'free')->firstOrFail();
        Setting::putMany(['signup_default_plan' => (string) $plan->id]);

        $user = app(CreateNewUser::class)->create([
            'name' => 'Newbie', 'email' => 'newbie@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $this->assertSame($plan->id, $user->plan_id);
    }
}
