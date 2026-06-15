<?php

use App\Models\User;

$u = User::where('email', 'admin@linkforge.test')->first();
$p = $u->bioPages()->updateOrCreate(
    ['slug' => 'verifytest'],
    ['title' => 'Sangeeth Thilakarathna Wijesinghe', 'is_published' => true, 'settings' => ['verified' => true, 'description' => 'Designer and maker.']],
);
echo 'slug=verifytest verified='.json_encode($p->setting('verified')).PHP_EOL;
