<?php

$u = App\Models\User::where('email', 'admin@linkforge.test')->first();
$p = $u->bioPages()->firstOrCreate(['slug' => 'demo-leads'], ['title' => 'Demo Leads', 'is_published' => true]);
App\Models\BioSubscriber::firstOrCreate(['bio_page_id' => $p->id, 'email' => 'ada@example.com'], ['name' => 'Ada Lovelace']);
App\Models\BioSubscriber::firstOrCreate(['bio_page_id' => $p->id, 'email' => 'grace@example.com'], ['name' => 'Grace Hopper']);
App\Models\BioMessage::firstOrCreate(['bio_page_id' => $p->id, 'message' => 'Love your work. Do you offer consulting?'], ['name' => 'Alan', 'email' => 'alan@example.com']);
echo 'page_id='.$p->id.PHP_EOL;
