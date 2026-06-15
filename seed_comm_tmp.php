<?php

use App\Models\User;
use App\Support\HtmlSanitizer;

$u = User::where('email', 'admin@linkforge.test')->first();
$p = $u->bioPages()->updateOrCreate(['slug' => 'demo-comm'], ['title' => 'Comm Demo', 'is_published' => true]);
$p->blocks()->delete();

$rows = [
    ['type' => 'tagline', 'content' => ['text' => 'Designer, maker, and occasional writer.']],
    ['type' => 'html', 'content' => ['html' => HtmlSanitizer::clean('<p>Hello <b>world</b> <a href="https://ok.com">safe link</a> <a href="javascript:alert(1)">bad link</a></p><script>alert("xss")</script><img src="x" onerror="alert(2)">')]],
    ['type' => 'vcard', 'content' => ['label' => 'Ada Lovelace', 'org' => 'Analytical Engines', 'title' => 'Mathematician', 'phone' => '+15551234567', 'email' => 'ada@example.com', 'url' => 'https://example.com']],
    ['type' => 'carousel', 'content' => ['images' => ['https://picsum.photos/seed/a/600/400', 'https://picsum.photos/seed/b/600/400', 'https://picsum.photos/seed/c/600/400']]],
    ['type' => 'paypal', 'content' => ['username' => 'adalovelace', 'amount' => '15', 'label' => 'Buy me a coffee']],
    ['type' => 'audio', 'content' => ['label' => 'Latest episode', 'url' => 'https://example.com/audio.mp3']],
    ['type' => 'pdf', 'content' => ['label' => 'Download my resume', 'url' => 'https://example.com/cv.pdf']],
    ['type' => 'videofile', 'content' => ['url' => 'https://example.com/clip.mp4']],
    ['type' => 'chat', 'content' => ['provider' => 'tawkto', 'id' => '5f000000000000/default']],
];
$sort = 0;
foreach ($rows as $r) {
    $p->blocks()->create(['type' => $r['type'], 'content' => $r['content'], 'sort' => $sort++, 'is_active' => true]);
}
echo 'slug=demo-comm blocks='.$p->blocks()->count().PHP_EOL;
echo 'html_stored='.($p->blocks()->where('type', 'html')->value('content')['html'] ?? '').PHP_EOL;
