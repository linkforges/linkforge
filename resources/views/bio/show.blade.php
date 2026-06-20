@php
    use App\Support\BioThemes;
    use App\Support\BioSocial;

    $d = BioThemes::resolve($page->theme);
    $font = BioThemes::fontFamily($d['font']);
    $heading = $page->title ?: '@'.$page->slug;
    $desc = $page->setting('description');
    $avatar = (array) $page->setting('avatar', ['display' => true, 'style' => 'circle']);
    $showAvatar = $avatar['display'] ?? true;
    $avatarShape = ['circle' => '9999px', 'rounded' => '20px', 'square' => '6px'][$avatar['style'] ?? 'circle'] ?? '9999px';
    $verified = (bool) $page->setting('verified', false);
    $social = $page->setting('social_position', 'top');
    $links = array_values(array_filter((array) ($page->social_links ?? []), fn ($s) => ! empty($s['url'])));

    // Background
    $bg = $d['bg'];
    $bgCss = match ($bg['type']) {
        'gradient' => "background:linear-gradient({$bg['gradAngle']}deg, {$bg['gradStart']}, {$bg['gradStop']});",
        'image' => ! empty($bg['image']) ? "background:#0f172a url('{$bg['image']}') center/cover no-repeat fixed;" : 'background:#0f172a;',
        default => "background:{$bg['color']};",
    };

    // Button style → inline CSS
    $b = $d['button'];
    $radius = ['rounded' => '14px', 'pill' => '9999px', 'square' => '8px'][$b['shape']] ?? '14px';
    $shadow = ['none' => 'none', 'sm' => '0 1px 2px rgba(0,0,0,.08)', 'lg' => '0 12px 28px rgba(0,0,0,.18)'][$b['shadow']] ?? 'none';
    $btnCss = "border-radius:{$radius};box-shadow:{$shadow};";
    if (! empty($b['frosted'])) {
        $btnCss .= "background:color-mix(in srgb, {$b['color']} 45%, transparent);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);color:{$b['textColor']};border:1px solid color-mix(in srgb,{$b['textColor']} 25%, transparent);";
    } elseif ($b['style'] === 'outline') {
        $btnCss .= "background:transparent;border:2px solid {$b['textColor']};color:{$b['textColor']};";
    } elseif ($b['style'] === 'soft') {
        $btnCss .= "background:color-mix(in srgb, {$b['color']} 90%, transparent);color:{$b['textColor']};";
    } else { // fill
        $btnCss .= "background:{$b['color']};color:{$b['textColor']};";
    }

    $avatarUrl = $avatar['image'] ?? null;
    $initial = strtoupper(mb_substr($page->title ?: $page->slug, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->setting('seo.title') ?: $heading }}</title>
    @if ($metaDesc = ($page->setting('seo.description') ?: $desc))<meta name="description" content="{{ $metaDesc }}">@endif
    <meta property="og:title" content="{{ $page->setting('seo.title') ?: $heading }}">
    @if ($ogImg = $page->setting('seo.image'))<meta property="og:image" content="{{ $ogImg }}">@endif
    @vite(['resources/css/app.css'])
    @include('partials.favicon')
    <style>
        body { font-family: {!! $font !!}; {!! $bgCss !!} color: {{ $d['textColor'] }}; }
        .bio-btn { {!! $btnCss !!} }
        .bio-btn:hover { transform: translateY(-2px); transition: transform .15s ease; }
        .bio-surface { {!! $btnCss !!} }
        .bio-muted { color: color-mix(in srgb, {{ $d['textColor'] }} 70%, transparent); }
        .bio-input { width: 100%; border: 0; border-radius: 10px; padding: .65rem .85rem; font-size: .875rem; color: #0f172a; background: rgba(255,255,255,.92); }
        .bio-input::placeholder { color: #94a3b8; }
        .bio-input:focus { outline: 2px solid rgba(255,255,255,.6); outline-offset: 1px; }
        .bio-html a { text-decoration: underline; }
        .bio-html ul, .bio-html ol { padding-left: 1.25rem; list-style: revert; }
        .bio-html h1, .bio-html h2, .bio-html h3 { font-weight: 700; }
        .bio-carousel { scrollbar-width: thin; }
        .bio-carousel::-webkit-scrollbar { height: 6px; }
        .bio-carousel::-webkit-scrollbar-thumb { background: color-mix(in srgb, currentColor 25%, transparent); border-radius: 9999px; }
    </style>
</head>
<body class="min-h-screen">
    @php $isRow = $d['headerLayout'] === 'row'; @endphp
    <div class="mx-auto flex min-h-screen max-w-md flex-col px-5 pb-8 {{ $d['headerLayout'] === 'banner' ? '' : 'pt-12' }}">

        @if (session('bio_form_ok'))
            <div class="mb-4 rounded-xl bg-white/95 px-4 py-3 text-center text-sm font-semibold text-emerald-700 shadow-sm">
                {{ session('bio_form_ok') === 'subscribe' ? 'Thanks for subscribing.' : 'Thanks. Your message has been sent.' }}
            </div>
        @endif

        @if ($d['headerLayout'] === 'banner')
            <div class="-mx-5 mb-[-44px] h-32" style="background:color-mix(in srgb, {{ $d['textColor'] }} 12%, transparent)"></div>
        @endif

        <div class="@if($isRow) flex items-center gap-4 @else flex flex-col items-center text-center @endif">
            @if ($showAvatar)
                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden text-2xl font-bold text-white shadow-lg ring-4 ring-white/30"
                     style="border-radius:{{ $avatarShape }}; background:{{ $avatarUrl ? 'transparent' : 'color-mix(in srgb, '.$d['textColor'].' 25%, transparent)' }}">
                    @if ($avatarUrl)<img src="{{ $avatarUrl }}" alt="" class="h-full w-full object-cover">@else{{ $initial }}@endif
                </div>
            @endif
            <div class="@if($isRow) text-left @else mt-4 @endif">
                <h1 class="text-xl font-bold @unless($isRow) text-center @endunless">{{ $heading }}@if ($verified) <svg class="inline h-5 w-5 align-middle" viewBox="0 0 24 24" fill="#3b82f6"><path d="M12 2 9.8 4.2 6.7 4 5.6 6.9 2.8 8.3 3.6 11.4 2 14l2.3 2.1.2 3.1 3 .7L9.4 22 12 20.6 14.6 22l1-2.9 3-.7.2-3.1L21 11.4l-1.6-2.6.8-3.1-2.8-1.4L16.3 4l-2.1.2z"/><path d="m10.5 14.6-2-2L9.7 11.4l1 1 2.8-2.8 1.2 1.2z" fill="#fff"/></svg>@endif</h1>
                @if ($desc)<p class="bio-muted mt-1.5 max-w-xs text-sm @unless($isRow) mx-auto @endunless">{{ $desc }}</p>@endif
            </div>
        </div>

        @if ($social === 'top' && count($links))
            @include('bio.partials.social', ['links' => $links])
        @endif

        <div class="mt-7 space-y-3.5">
            @foreach ($page->blocks as $block)
                @php $content = (array) $block->content; @endphp
                @switch($block->type)
                    @case('heading')
                        <h2 class="pt-3 text-center text-base font-bold">{{ $content['text'] ?? '' }}</h2>
                        @break
                    @case('text')
                        <p class="bio-muted text-center text-sm leading-relaxed">{{ $content['text'] ?? '' }}</p>
                        @break
                    @case('image')
                        @if (! empty($content['url']))<img src="{{ $content['url'] }}" alt="{{ $content['label'] ?? '' }}" class="w-full rounded-2xl shadow-sm">@endif
                        @break
                    @case('divider')
                        <hr class="my-1 h-px border-0" style="background:color-mix(in srgb, {{ $d['textColor'] }} 22%, transparent)">
                        @break
                    @case('phone')
                        <a href="tel:{{ $content['phone'] ?? '' }}" class="bio-btn flex items-center justify-center px-5 py-4 text-sm font-semibold">{{ $content['label'] ?? 'Call' }}</a>
                        @break
                    @case('email')
                        <a href="mailto:{{ $content['email'] ?? '' }}" class="bio-btn flex items-center justify-center px-5 py-4 text-sm font-semibold">{{ $content['label'] ?? 'Email me' }}</a>
                        @break
                    @case('whatsapp')
                        @php $wa = 'https://wa.me/'.preg_replace('/[^0-9]/', '', $content['phone'] ?? '').(! empty($content['message']) ? '?text='.urlencode($content['message']) : ''); @endphp
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="bio-btn flex items-center justify-center px-5 py-4 text-sm font-semibold">{{ $content['label'] ?? 'WhatsApp' }}</a>
                        @break
                    @case('featured')
                        @php $href = $block->id ? route('bio.track', [$page->slug, $block->id]) : ($content['url'] ?? '#'); @endphp
                        <a href="{{ $href }}" target="_blank" rel="noopener nofollow" class="bio-btn block overflow-hidden p-0 text-left">
                            @if (! empty($content['image']))<img src="{{ $content['image'] }}" alt="" class="h-32 w-full object-cover">@endif
                            <span class="block px-5 py-3.5 text-sm font-semibold">{{ $content['label'] ?? 'Link' }}</span>
                        </a>
                        @break
                    @case('video')
                    @case('embed')
                        @php $e = \App\Support\BioEmbed::resolve($content['url'] ?? ''); @endphp
                        @if ($e)<div class="overflow-hidden rounded-2xl shadow-sm" style="{{ $e['style'] }}"><iframe src="{{ $e['src'] }}" class="h-full w-full" style="border:0" loading="lazy" allow="autoplay; encrypted-media; fullscreen; clipboard-write" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>@endif
                        @break
                    @case('map')
                        @php $mq = trim($content['query'] ?? ''); @endphp
                        @if ($mq !== '')<div class="overflow-hidden rounded-2xl shadow-sm" style="aspect-ratio:16/9"><iframe src="https://maps.google.com/maps?q={{ urlencode($mq) }}&output=embed" class="h-full w-full" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>@endif
                        @break
                    @case('countdown')
                        @php $cd = $content['date'] ?? ''; @endphp
                        @if ($cd)
                            <div class="bio-btn flex flex-col items-center px-5 py-4" data-countdown="{{ $cd }}">
                                @if (! empty($content['label']))<span class="text-xs font-medium opacity-80">{{ $content['label'] }}</span>@endif
                                <span class="countdown-out text-xl font-bold tracking-wide">--</span>
                            </div>
                        @endif
                        @break
                    @case('faq')
                        @if (! empty($content['label']))<h2 class="pt-3 text-center text-base font-bold">{{ $content['label'] }}</h2>@endif
                        @php
                            $faqs = array_filter(array_map(function ($line) {
                                $parts = array_map('trim', explode('|', $line, 2));
                                return $parts[0] !== '' ? ['q' => $parts[0], 'a' => $parts[1] ?? ''] : null;
                            }, preg_split('/\r\n|\r|\n/', $content['text'] ?? '')));
                        @endphp
                        @foreach ($faqs as $faq)
                            <details class="bio-btn overflow-hidden px-0">
                                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-3.5 text-sm font-semibold">{{ $faq['q'] }}<span class="ml-2 text-lg leading-none opacity-50">+</span></summary>
                                @if ($faq['a'] !== '')<div class="px-5 pb-4 text-sm leading-relaxed opacity-80">{{ $faq['a'] }}</div>@endif
                            </details>
                        @endforeach
                        @break
                    @case('product')
                        <div class="bio-btn overflow-hidden p-0 text-left">
                            @if (! empty($content['image']))<img src="{{ $content['image'] }}" alt="" class="h-44 w-full object-cover">@endif
                            <div class="px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-sm font-semibold">{{ $content['label'] ?? '' }}</span>
                                    @if (! empty($content['price']))<span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold" style="background:color-mix(in srgb, currentColor 16%, transparent)">{{ $content['price'] }}</span>@endif
                                </div>
                                @if (! empty($content['text']))<p class="mt-1.5 text-sm leading-relaxed opacity-75">{{ $content['text'] }}</p>@endif
                                @if (! empty($content['url']))<a href="{{ $content['url'] }}" target="_blank" rel="noopener nofollow" class="mt-3.5 block w-full rounded-lg py-2.5 text-center text-sm font-semibold" style="background:color-mix(in srgb, currentColor 14%, transparent)">Buy now</a>@endif
                            </div>
                        </div>
                        @break
                    @case('newsletter')
                        <form method="POST" action="{{ route('bio.subscribe', $page->slug) }}" class="bio-surface space-y-2.5 p-5 text-left">
                            @csrf
                            @if (! empty($content['label']))<p class="text-sm font-semibold">{{ $content['label'] }}</p>@endif
                            @if (! empty($content['text']))<p class="text-sm opacity-75">{{ $content['text'] }}</p>@endif
                            <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px" value="">
                            <input type="email" name="email" required placeholder="you@email.com" class="bio-input">
                            <button type="submit" class="w-full rounded-lg py-2.5 text-sm font-semibold" style="background:color-mix(in srgb, currentColor 16%, transparent)">{{ $content['button'] ?: 'Subscribe' }}</button>
                        </form>
                        @break
                    @case('contact')
                        <form method="POST" action="{{ route('bio.contact', $page->slug) }}" class="bio-surface space-y-2.5 p-5 text-left">
                            @csrf
                            @if (! empty($content['label']))<p class="text-sm font-semibold">{{ $content['label'] }}</p>@endif
                            @if (! empty($content['text']))<p class="text-sm opacity-75">{{ $content['text'] }}</p>@endif
                            <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px" value="">
                            <input type="text" name="name" placeholder="Your name" class="bio-input">
                            <input type="email" name="email" placeholder="Email (optional)" class="bio-input">
                            <textarea name="message" required rows="3" placeholder="Your message" class="bio-input"></textarea>
                            <button type="submit" class="w-full rounded-lg py-2.5 text-sm font-semibold" style="background:color-mix(in srgb, currentColor 16%, transparent)">{{ $content['button'] ?: 'Send message' }}</button>
                        </form>
                        @break
                    @case('rss')
                        @php $rssItems = \App\Support\BioRss::items($content['url'] ?? '', (int) ($content['count'] ?? 5)); @endphp
                        @if (! empty($content['label']))<h2 class="pt-3 text-center text-base font-bold">{{ $content['label'] }}</h2>@endif
                        @foreach ($rssItems as $item)
                            <a href="{{ $item['url'] ?: '#' }}" target="_blank" rel="noopener nofollow" class="bio-btn block px-5 py-3.5 text-sm font-medium">{{ $item['title'] }}</a>
                        @endforeach
                        @break
                    @case('tagline')
                        <p class="px-2 py-4 text-center text-lg font-semibold leading-snug">{{ $content['text'] ?? '' }}</p>
                        @break
                    @case('html')
                        <div class="bio-html text-sm leading-relaxed">{!! $content['html'] ?? '' !!}</div>
                        @break
                    @case('vcard')
                        @php $vc = $block->id ? route('bio.vcard', [$page->slug, $block->id]) : '#'; @endphp
                        <a href="{{ $vc }}" class="bio-btn flex items-center justify-center gap-2 px-5 py-4 text-sm font-semibold">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h3M15 12h3M7 16h10"/></svg>
                            {{ $content['label'] ?: 'Save contact' }}
                        </a>
                        @break
                    @case('carousel')
                        <div class="bio-carousel flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2">
                            @foreach (($content['images'] ?? []) as $img)
                                <img src="{{ $img }}" alt="" class="h-48 w-72 shrink-0 snap-center rounded-2xl object-cover shadow-sm">
                            @endforeach
                        </div>
                        @break
                    @case('paypal')
                        @php $ppUrl = 'https://www.paypal.com/paypalme/'.$content['username'].(! empty($content['amount']) ? '/'.$content['amount'] : ''); @endphp
                        <a href="{{ $ppUrl }}" target="_blank" rel="noopener nofollow" class="bio-btn flex items-center justify-center gap-2 px-5 py-4 text-sm font-semibold">{{ $content['label'] ?: 'Pay with PayPal' }}</a>
                        @break
                    @case('audio')
                        <div class="bio-surface px-4 py-3">
                            @if (! empty($content['label']))<p class="mb-2 text-sm font-semibold">{{ $content['label'] }}</p>@endif
                            <audio controls preload="none" src="{{ $content['url'] ?? '' }}" class="w-full"></audio>
                        </div>
                        @break
                    @case('pdf')
                        <a href="{{ $content['url'] ?? '#' }}" target="_blank" rel="noopener" class="bio-btn flex items-center justify-center gap-2 px-5 py-4 text-sm font-semibold">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                            {{ $content['label'] ?: 'View PDF' }}
                        </a>
                        @break
                    @case('videofile')
                        <video controls preload="none" src="{{ $content['url'] ?? '' }}" class="w-full rounded-2xl shadow-sm"></video>
                        @break
                    @case('chat')
                        {!! \App\Support\BioChat::snippet($content['provider'] ?? '', $content['id'] ?? '') !!}
                        @break
                    @case('spacer')
                        <div aria-hidden="true" style="height:{{ ['sm' => '8px', 'md' => '24px', 'lg' => '52px'][$content['size'] ?? 'md'] ?? '24px' }}"></div>
                        @break
                    @case('gallery')
                        @if (! empty($content['images']))
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($content['images'] as $img)<img src="{{ $img }}" alt="" class="aspect-square w-full rounded-xl object-cover shadow-sm">@endforeach
                            </div>
                        @endif
                        @break
                    @case('apps')
                        <div class="flex flex-wrap justify-center gap-2.5">
                            @if (! empty($content['ios']))
                                <a href="{{ $content['ios'] }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-slate-200">
                                    <img src="{{ asset('vendor/social/appstore.svg') }}" alt="" class="h-6 w-6"><span class="text-sm font-semibold text-slate-800">App Store</span>
                                </a>
                            @endif
                            @if (! empty($content['android']))
                                <a href="{{ $content['android'] }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-slate-200">
                                    <img src="{{ asset('vendor/social/googleplay.svg') }}" alt="" class="h-6 w-6"><span class="text-sm font-semibold text-slate-800">Google Play</span>
                                </a>
                            @endif
                        </div>
                        @break
                    @case('testimonial')
                        <figure class="bio-surface px-5 py-4 text-left">
                            @if (! empty($content['text']))<blockquote class="text-sm italic leading-relaxed">&ldquo;{{ $content['text'] }}&rdquo;</blockquote>@endif
                            @if (! empty($content['label']) || ! empty($content['image']))
                                <figcaption class="mt-3 flex items-center gap-2.5">
                                    @if (! empty($content['image']))<img src="{{ $content['image'] }}" alt="" class="h-8 w-8 rounded-full object-cover">@endif
                                    @if (! empty($content['label']))<span class="text-sm font-semibold">{{ $content['label'] }}</span>@endif
                                </figcaption>
                            @endif
                        </figure>
                        @break
                    @default
                        @php $href = $block->id ? route('bio.track', [$page->slug, $block->id]) : ($content['url'] ?? '#'); @endphp
                        <a href="{{ $href }}" target="_blank" rel="noopener nofollow"
                           class="bio-btn flex items-center justify-center px-5 py-4 text-sm font-semibold">{{ $content['label'] ?? 'Link' }}</a>
                @endswitch
            @endforeach
        </div>

        <div class="mt-auto pt-10">
            @if ($social === 'bottom' && count($links))
                @include('bio.partials.social', ['links' => $links])
            @endif

            @unless ($page->setting('hide_branding', false))
                <p class="bio-muted mt-6 text-center text-xs">Powered by {{ config('linkforge.name') }}</p>
            @endunless
        </div>
    </div>
    <script>
        (function () {
            function tick() {
                document.querySelectorAll('[data-countdown]').forEach(function (el) {
                    var out = el.querySelector('.countdown-out');
                    if (!out) return;
                    var t = new Date(el.getAttribute('data-countdown')).getTime() - Date.now();
                    if (isNaN(t)) { out.textContent = ''; return; }
                    if (t <= 0) { out.textContent = 'Expired'; return; }
                    var d = Math.floor(t / 86400000), h = Math.floor(t / 3600000) % 24, m = Math.floor(t / 60000) % 60, s = Math.floor(t / 1000) % 60;
                    var p = function (n) { return String(n).padStart(2, '0'); };
                    out.textContent = (d > 0 ? d + 'd ' : '') + p(h) + ':' + p(m) + ':' + p(s);
                });
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
</body>
</html>
