<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OperabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_admin_can_send_a_test_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@x.test']);

        $this->actingAs($admin)->postJson(route('admin.settings.email.test'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_test_email_requires_an_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.settings.email.test'))
            ->assertForbidden();
    }

    public function test_admin_can_run_a_maintenance_action_and_it_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.maintenance'), ['action' => 'clear-cache'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'maintenance.clear-cache']);
    }

    public function test_maintenance_rejects_an_unknown_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.maintenance'), ['action' => 'rm-rf'])
            ->assertStatus(400);
    }

    public function test_all_new_admin_screens_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.broadcast'))->assertOk()->assertSee('Broadcast');
        $this->actingAs($admin)->get(route('admin.pages.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.pages.create'))->assertOk();

        $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'general']))->assertOk()
            ->assertSee('Localization')
            ->assertSee('Maintenance tools')
            ->assertSee('Announcement banner')
            ->assertSee('Cookie consent')
            ->assertSee('Blocked email domains');

        $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'email']))->assertOk()->assertSee('Send test email');
        $this->actingAs($admin)->get(route('admin.users'))->assertOk()->assertSee('Apply to selected');
    }
}
