<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_support_index_and_create_render(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('support.index'))->assertOk()->assertSee('No tickets yet');
        $this->actingAs($user)->get(route('support.create'))->assertOk()->assertSee('New support ticket');
    }

    public function test_user_can_open_a_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('support.store'), [
            'subject' => 'Cannot create links', 'category' => 'technical', 'priority' => 'high', 'message' => 'It fails with an error.',
        ])->assertRedirect();

        $ticket = Ticket::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('open', $ticket->status);
        $this->assertSame('high', $ticket->priority);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'author_role' => 'user', 'body' => 'It fails with an error.']);
    }

    public function test_ticket_is_owner_only(): void
    {
        $ticket = User::factory()->create()->tickets()->create(['subject' => 'Mine', 'status' => 'open', 'last_reply_by' => 'user']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('support.show', $ticket))->assertForbidden();
        $this->actingAs($intruder)->post(route('support.reply', $ticket), ['message' => 'sneaky'])->assertForbidden();
    }

    public function test_full_conversation_flow_and_status_transitions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('support.store'), ['subject' => 'Help me', 'category' => 'general', 'priority' => 'normal', 'message' => 'First message']);
        $ticket = Ticket::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('open', $ticket->status);

        // Staff reply -> answered + audited.
        $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), ['message' => 'Here is how'])->assertRedirect();
        $this->assertSame('answered', $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'author_role' => 'admin']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.reply', 'user_id' => $admin->id]);

        // Customer reply -> reopens.
        $this->actingAs($user)->post(route('support.reply', $ticket), ['message' => 'Still stuck'])->assertRedirect();
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertSame(3, $ticket->messages()->count());

        // Customer closes.
        $this->actingAs($user)->post(route('support.close', $ticket))->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_admin_tickets_require_admin_and_support_status_priority_update(): void
    {
        $ticket = User::factory()->create()->tickets()->create(['subject' => 'Need help here', 'status' => 'open', 'last_reply_by' => 'user']);

        $this->actingAs(User::factory()->create())->get(route('admin.tickets'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.tickets'))->assertOk()->assertSee('Need help here');
        $this->actingAs($admin)->get(route('admin.tickets.show', $ticket))->assertOk()->assertSee('Need help here');

        $this->actingAs($admin)->put(route('admin.tickets.update', $ticket), ['status' => 'closed', 'priority' => 'low'])->assertRedirect();
        $ticket->refresh();
        $this->assertSame('closed', $ticket->status);
        $this->assertSame('low', $ticket->priority);
    }
}
