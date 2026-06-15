<?php

use App\Models\Ticket;
use App\Models\User;

$u = User::where('email', 'admin@linkforge.test')->first();
$t = Ticket::create([
    'user_id' => $u->id,
    'subject' => 'My custom domain is not verifying',
    'category' => 'technical',
    'priority' => 'high',
    'status' => 'open',
    'last_reply_at' => now(),
    'last_reply_by' => 'user',
]);
$t->messages()->create(['user_id' => $u->id, 'author_role' => 'user', 'body' => "Hi team,\n\nI added go.mydomain.com and the DNS records but it still shows pending after a few hours. Can you check?", 'created_at' => now()->subMinutes(30)]);
$t->addMessage("Thanks for reaching out! DNS can take up to 24h to propagate. I can see your CNAME is correct. I've re-triggered verification on our side. It should flip to Active shortly.", 'admin', $u->id);
echo 'ticket='.$t->id.' status='.$t->fresh()->status.PHP_EOL;
