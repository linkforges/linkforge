<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_staff_reply_emails_the_customer_with_rendered_template(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Sam']);
        $ticket = $user->tickets()->create(['subject' => 'DNS issue', 'status' => 'open', 'last_reply_by' => 'user']);

        $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), ['message' => 'Fixed it'])->assertRedirect();

        Mail::assertSent(TemplatedMail::class, function ($m) use ($user, $ticket) {
            return $m->hasTo($user->email)
                && str_contains($m->subjectLine, (string) $ticket->id)
                && str_contains($m->bodyText, 'Sam')           // {{ name }} rendered
                && str_contains($m->bodyText, 'DNS issue');     // {{ ticket_subject }} rendered
        });
    }

    public function test_disabled_event_is_not_sent(): void
    {
        Mail::fake();
        EmailTemplate::create(['event' => 'ticket_reply', 'subject' => 'x', 'body' => 'y', 'enabled' => false]);

        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = User::factory()->create()->tickets()->create(['subject' => 's', 'status' => 'open', 'last_reply_by' => 'user']);

        $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), ['message' => 'hi']);

        Mail::assertNotSent(TemplatedMail::class);
    }

    public function test_opening_a_ticket_emails_customer_and_staff(): void
    {
        Mail::fake();
        User::factory()->create(['role' => 'admin', 'email' => 'staff@x.test']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('support.store'), ['subject' => 'Help', 'category' => 'general', 'priority' => 'normal', 'message' => 'hi']);

        Mail::assertSent(TemplatedMail::class, fn ($m) => $m->hasTo($user->email));   // ticket_opened
        Mail::assertSent(TemplatedMail::class, fn ($m) => $m->hasTo('staff@x.test'));  // admin_new_ticket
    }

    public function test_admin_can_edit_and_toggle_an_email_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'section' => 'email_template', 'event' => 'welcome',
            'subject' => 'Hey {{ name }}', 'body' => 'Custom welcome copy', 'enabled' => '1',
        ])->assertRedirect(route('admin.settings', ['tab' => 'email']));

        $this->assertDatabaseHas('email_templates', ['event' => 'welcome', 'subject' => 'Hey {{ name }}', 'enabled' => 1]);

        $this->actingAs($admin)->put(route('admin.settings.update'), ['section' => 'email_template', 'event' => 'bogus', 'subject' => 'a', 'body' => 'b'])
            ->assertSessionHas('error');
    }

    public function test_email_tab_renders_template_manager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'email']))->assertOk()
            ->assertSee('Email notifications')
            ->assertSee('New reply from support')
            ->assertSee('SMTP server');
    }
}
