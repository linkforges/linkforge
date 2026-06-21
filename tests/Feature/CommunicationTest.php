<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_announcement_banner_shows_when_enabled(): void
    {
        Setting::putMany(['announcement_enabled' => '1', 'announcement_text' => 'Heads up: scheduled maintenance Sunday.']);

        $this->get('/')->assertOk()->assertSee('scheduled maintenance Sunday', false);
    }

    public function test_announcement_banner_is_hidden_when_disabled(): void
    {
        Setting::putMany(['announcement_enabled' => '0', 'announcement_text' => 'A hidden message here.']);

        $this->get('/')->assertOk()->assertDontSee('A hidden message here', false);
    }

    public function test_admin_can_broadcast_to_all_users(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create();

        $this->actingAs($admin)->post(route('admin.broadcast.send'), [
            'subject' => 'Hello everyone', 'message' => 'A broadcast.', 'audience' => 'all',
        ])->assertRedirect();

        Mail::assertQueued(TemplatedMail::class);
    }

    public function test_broadcast_requires_an_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.broadcast.send'), ['subject' => 'x', 'message' => 'y', 'audience' => 'all'])
            ->assertForbidden();
    }

    public function test_admin_can_email_a_single_user(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['email' => 'target@x.test']);

        $this->actingAs($admin)->post(route('admin.users.email', $user), [
            'subject' => 'Just you', 'message' => 'Hi there.',
        ])->assertRedirect();

        Mail::assertSent(TemplatedMail::class);
    }

    public function test_an_abuse_report_alerts_staff(): void
    {
        Mail::fake();
        User::factory()->create(['role' => 'admin', 'email' => 'staff@x.test']);

        $this->post(route('report.store'), ['reason' => 'This link is spam and should be removed.'])
            ->assertRedirect();

        Mail::assertSent(TemplatedMail::class);
    }
}
