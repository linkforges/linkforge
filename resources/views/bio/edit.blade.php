@php
    use App\Support\BioThemes;
    use App\Support\BioSocial;

    $design = BioThemes::resolve($page?->theme);
    $s = $page?->settings ?? [];
    $avatar = (array) ($s['avatar'] ?? ['display' => true, 'style' => 'circle']);
    $seo = (array) ($s['seo'] ?? []);
    $hasPassword = ! empty($s['password']);
    $canBrand = app(\App\Services\Billing\PlanGate::class)->allows(auth()->user(), 'white_label');
    $bg = $design['bg'];
    $btn = $design['button'];
    $hl = $design['headerLayout'];
    $initial = [
        'design' => $design,
        'settings' => $s,
        'social' => $page?->social_links ?? [],
        // Spread the whole content so every field (phone, email, image, query, date, ...) round-trips.
        'blocks' => $page ? $page->blocks->map(fn ($b) => array_merge(['type' => $b->type], (array) $b->content))->all() : [],
    ];
    $blockIcons = [
        'link' => '<path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/>',
        'featured' => '<path d="m12 2 3 6.5 7 .6-5.3 4.6 1.6 6.8L12 17l-6.2 3.5 1.6-6.8L2 9.1l7-.6z"/>',
        'heading' => '<path d="M6 4v16M18 4v16M6 12h12"/>',
        'text' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
        'video' => '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="m22 8-6 4 6 4z"/>',
        'embed' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="m8 21 8 0M12 17v4"/><path d="m10 8 4 2.5-4 2.5z"/>',
        'map' => '<path d="M9 18 3 21V6l6-3 6 3 6-3v15l-6 3-6-3z"/><path d="M9 3v15M15 6v15"/>',
        'countdown' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M9 2h6"/>',
        'faq' => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 4.4 2.6c-.9.5-1.5 1-1.5 2.4"/><path d="M12 17h.01"/>',
        'product' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'newsletter' => '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'contact' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'rss' => '<path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/>',
        'tagline' => '<path d="M3 21c3-1 5-3 5-6V5a2 2 0 0 1 2-2h2M14 21c3-1 5-3 5-6V5a2 2 0 0 1 2-2"/>',
        'html' => '<path d="m8 3-5 9 5 9M16 3l5 9-5 9M14 4l-4 16"/>',
        'vcard' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h3M15 12h3M7 16h10"/>',
        'carousel' => '<rect x="7" y="5" width="10" height="14" rx="2"/><path d="M4 8v8M20 8v8"/>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h.01M12 10h.01M16 10h.01"/>',
        'paypal' => '<path d="M7 11h9a4 4 0 0 0 0-8H8L5 21h4l1-6"/>',
        'audio' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'pdf' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'videofile' => '<rect x="2" y="2" width="20" height="20" rx="2.5"/><path d="m10 8 6 4-6 4z"/>',
        'spacer' => '<path d="M12 3v18M8 7l4-4 4 4M8 17l4 4 4-4"/>',
        'gallery' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'apps' => '<rect x="5" y="2" width="14" height="20" rx="3"/><path d="M11 18h2"/>',
        'testimonial' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h6M8 13h4"/>',
        'divider' => '<path d="M5 12h14"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'email' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'whatsapp' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
    ];
    // Categorised block palette: [key, label, color] — colour drives a tinted icon chip.
    // A 'logo:name' colour renders the real brand logo from public/vendor/social instead.
    $blockCats = [
        'Basics' => [['link', 'Link', '#0ea5e9'], ['featured', 'Featured', '#f59e0b'], ['heading', 'Heading', '#475569'], ['text', 'Text', '#475569'], ['tagline', 'Tagline', '#14b8a6'], ['image', 'Image', '#10b981'], ['divider', 'Divider', '#94a3b8'], ['spacer', 'Spacer', '#94a3b8']],
        'Media' => [['video', 'Video', '#ef4444'], ['embed', 'Embed', '#10b981'], ['audio', 'Audio', '#f97316'], ['videofile', 'Video file', '#ef4444'], ['pdf', 'PDF', '#dc2626'], ['carousel', 'Carousel', '#0ea5e9'], ['gallery', 'Gallery', '#0ea5e9']],
        'Commerce & forms' => [['product', 'Product', '#10b981'], ['paypal', 'PayPal', 'logo:paypal'], ['apps', 'App buttons', '#0f172a'], ['newsletter', 'Newsletter', '#f59e0b'], ['contact', 'Contact form', '#0ea5e9']],
        'Contact' => [['phone', 'Phone', '#10b981'], ['email', 'Email', '#0ea5e9'], ['whatsapp', 'WhatsApp', 'logo:whatsapp'], ['vcard', 'vCard', '#475569']],
        'Widgets' => [['map', 'Map', '#10b981'], ['countdown', 'Countdown', '#f59e0b'], ['faq', 'FAQ', '#14b8a6'], ['testimonial', 'Testimonial', '#f59e0b'], ['rss', 'RSS feed', '#f97316'], ['html', 'HTML', '#475569'], ['chat', 'Live chat', '#10b981']],
    ];
    $tabBtn = 'flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition aria-pressed:bg-brand-600 aria-pressed:text-white text-slate-500 hover:bg-slate-100';
    $segBtn = 'flex-1 rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition aria-pressed:bg-brand-50 aria-pressed:text-brand-700 aria-pressed:ring-1 aria-pressed:ring-brand-500/30';
@endphp

<x-app-layout :title="$page ? 'Edit bio page' : 'Create bio page'">
    <x-slot:header>{{ $page ? 'Edit bio page' : 'Create bio page' }}</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>@endif

    <script type="application/json" id="bio-initial">{!! json_encode($initial, JSON_HEX_TAG | JSON_HEX_APOS) !!}</script>
    <script type="application/json" id="bio-templates">{!! json_encode(BioThemes::templates(), JSON_HEX_TAG | JSON_HEX_APOS) !!}</script>

    <div id="bio-builder" data-preview-url="{{ route('bio.preview') }}" data-upload-url="{{ route('bio.upload') }}" data-upload-file-url="{{ route('bio.upload-file') }}" class="grid gap-6 lg:grid-cols-[1fr_390px]">
        {{-- ====== left: form ====== --}}
        <form method="POST" action="{{ $page ? route('bio.update', $page) : route('bio.store') }}" class="space-y-5">
            @csrf
            @if ($page) @method('PUT') @endif
            <input type="hidden" name="design" id="bio-field-design">
            <input type="hidden" name="settings" id="bio-field-settings">
            <input type="hidden" name="social" id="bio-field-social">
            <input type="hidden" name="blocks" id="bio-field-blocks">

            {{-- page bar --}}
            <div class="lf-card grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <label class="lf-label" for="bio-slug">Handle</label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">{{ request()->getHost() }}/</span>
                        <input id="bio-slug" name="slug" value="{{ old('slug', $page?->slug) }}" required class="block w-full rounded-r-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-none" placeholder="yourname">
                    </div>
                    @error('slug')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lf-label" for="bio-title">Display name</label>
                    <input id="bio-title" name="title" value="{{ old('title', $page?->title) }}" class="lf-input" placeholder="Your name or brand">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page?->is_published)) class="h-4 w-4 rounded border-slate-300 text-brand-600">
                    Published (publicly visible)
                </label>
            </div>

            {{-- tabs --}}
            <div class="grid grid-cols-4 gap-2 rounded-xl bg-white p-1.5 shadow-sm ring-1 ring-slate-200">
                <button type="button" data-tab-btn="content" aria-pressed="true" class="{{ $tabBtn }}">Content</button>
                <button type="button" data-tab-btn="social" aria-pressed="false" class="{{ $tabBtn }}">Social</button>
                <button type="button" data-tab-btn="design" aria-pressed="false" class="{{ $tabBtn }}">Design</button>
                <button type="button" data-tab-btn="settings" aria-pressed="false" class="{{ $tabBtn }}">Settings</button>
            </div>

            {{-- CONTENT --}}
            <div data-tab="content">
                <div class="lf-card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">Blocks</h3>
                    <div id="bio-blocks-list" class="mt-4 space-y-3"></div>
                    <p class="mt-5 mb-3 text-xs font-medium text-slate-400">Add a block</p>
                    <div class="space-y-4">
                        @foreach ($blockCats as $catName => $catBlocks)
                            <div>
                                <p class="mb-2 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">{{ $catName }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($catBlocks as [$t, $l, $c])
                                        @php $isLogo = str_starts_with($c, 'logo:'); @endphp
                                        <button type="button" data-add-block="{{ $t }}" class="group inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white py-1.5 pr-3 pl-1.5 text-sm font-medium text-slate-700 transition hover:border-brand-400 hover:bg-brand-50 hover:shadow-sm">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-md" @unless($isLogo) style="background-color:{{ $c }}1f" @endunless>
                                                @if ($isLogo)
                                                    <img src="{{ asset('vendor/social/'.substr($c, 5).'.svg') }}" alt="" class="h-4 w-4">
                                                @else
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $blockIcons[$t] ?? '' !!}</svg>
                                                @endif
                                            </span>
                                            {{ $l }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- SOCIAL --}}
            @php $socialOptions = collect(BioSocial::options())->map(fn ($label, $k) => ['key' => $k, 'label' => $label, 'icon' => BioSocial::iconUrl($k)])->values()->all(); @endphp
            <script type="application/json" id="bio-social-options-data">{!! json_encode($socialOptions, JSON_HEX_TAG | JSON_HEX_APOS) !!}</script>
            <div data-tab="social" class="hidden">
                <div class="lf-card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">Social links</h3>
                    <div id="bio-social-list" class="mt-4 space-y-2"></div>

                    {{-- Searchable platform picker --}}
                    <div class="relative mt-3" id="bio-social-picker">
                        <button type="button" id="bio-social-toggle" class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                Add a social link
                            </span>
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div id="bio-social-panel" class="absolute z-30 mt-1 hidden w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                            <div class="border-b border-slate-100 p-2">
                                <input type="text" id="bio-social-search" autocomplete="off" placeholder="Search platforms..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-none">
                            </div>
                            <div id="bio-social-options" class="max-h-64 overflow-y-auto p-1"></div>
                        </div>
                    </div>

                    <label class="mt-5 block text-xs text-slate-500" data-settings-pane>Icon position
                        <select id="bio-social-position" class="lf-input mt-1">
                            @foreach (['top' => 'Below header', 'bottom' => 'Bottom of page', 'off' => 'Hidden'] as $k => $l)
                                <option value="{{ $k }}" @selected(($s['social_position'] ?? 'top') === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            {{-- DESIGN --}}
            <div data-tab="design" class="hidden" data-design-pane>
                <div class="lf-card divide-y divide-slate-100">
                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Premium templates</h3>
                        <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                            @foreach (BioThemes::templates() as $i => $t)
                                @php $tb = $t['bg']; $preview = $tb['type'] === 'gradient' ? "linear-gradient({$tb['gradAngle']}deg,{$tb['gradStart']},{$tb['gradStop']})" : ($tb['type'] === 'color' ? $tb['color'] : '#0f172a'); @endphp
                                <button type="button" data-template-index="{{ $i }}" class="group overflow-hidden rounded-xl border border-slate-200 transition hover:ring-2 hover:ring-brand-500/40">
                                    <span class="flex h-16 items-center justify-center" style="background:{{ $preview }}">
                                        <span class="rounded-md px-3 py-1 text-[10px] font-semibold" style="background:{{ $t['button']['color'] }};color:{{ $t['button']['textColor'] }}">Link</span>
                                    </span>
                                    <span class="block bg-white py-1 text-center text-[11px] font-medium text-slate-600">{{ $t['name'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Header layout</h3>
                        <div class="flex gap-2">
                            @foreach (['classic' => 'Centered', 'banner' => 'Banner', 'row' => 'Row'] as $k => $l)
                                <button type="button" data-header="{{ $k }}" aria-pressed="{{ $hl === $k ? 'true' : 'false' }}" class="{{ $segBtn }}">{{ $l }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Typography</h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="text-xs text-slate-500">Font
                                <select id="bio-font" class="lf-input mt-1">
                                    @foreach (BioThemes::fontOptions() as $k => $l)<option value="{{ $k }}" @selected($design['font'] === $k)>{{ $l }}</option>@endforeach
                                </select>
                            </label>
                            <label class="text-xs text-slate-500">Text color <input type="color" id="bio-textcolor" value="{{ $design['textColor'] }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        </div>
                    </div>

                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Background</h3>
                        <div class="flex gap-2">
                            @foreach (['color' => 'Solid', 'gradient' => 'Gradient', 'image' => 'Image'] as $k => $l)
                                <button type="button" data-bgtype="{{ $k }}" aria-pressed="{{ $bg['type'] === $k ? 'true' : 'false' }}" class="{{ $segBtn }}">{{ $l }}</button>
                            @endforeach
                        </div>
                        <div data-bg-color-fields class="mt-3 @if($bg['type'] !== 'color') hidden @endif">
                            <label class="text-xs text-slate-500">Color <input type="color" id="bio-bg-color" value="{{ $bg['color'] ?? '#f8fafc' }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        </div>
                        <div data-bg-gradient-fields class="mt-3 grid gap-3 sm:grid-cols-3 @if($bg['type'] !== 'gradient') hidden @endif">
                            <label class="text-xs text-slate-500">Start <input type="color" id="bio-grad-start" value="{{ $bg['gradStart'] ?? '#10b981' }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                            <label class="text-xs text-slate-500">Stop <input type="color" id="bio-grad-stop" value="{{ $bg['gradStop'] ?? '#0f766e' }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                            <label class="text-xs text-slate-500">Angle <input type="range" id="bio-grad-angle" min="0" max="360" value="{{ $bg['gradAngle'] ?? 160 }}" class="mt-3 block w-full"></label>
                        </div>
                        <div data-bg-image-fields class="mt-3 @if($bg['type'] !== 'image') hidden @endif">
                            <label class="text-xs text-slate-500" for="bio-bg-image">Background image</label>
                            <div class="mt-1 flex gap-2">
                                <input type="url" id="bio-bg-image" value="{{ $bg['image'] ?? '' }}" class="lf-input" placeholder="Paste URL or upload">
                                <button type="button" data-upload-target="bio-bg-image" class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Upload</button>
                            </div>
                        </div>
                    </div>

                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Buttons</h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="text-xs text-slate-500">Color <input type="color" id="bio-btn-color" value="{{ $btn['color'] }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                            <label class="text-xs text-slate-500">Text color <input type="color" id="bio-btn-textcolor" value="{{ $btn['textColor'] }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                            <label class="text-xs text-slate-500">Style
                                <select id="bio-btn-style" class="lf-input mt-1">@foreach (['fill' => 'Fill', 'soft' => 'Soft', 'outline' => 'Outline'] as $k => $l)<option value="{{ $k }}" @selected($btn['style'] === $k)>{{ $l }}</option>@endforeach</select>
                            </label>
                            <label class="text-xs text-slate-500">Shape
                                <select id="bio-btn-shape" class="lf-input mt-1">@foreach (['rounded' => 'Rounded', 'pill' => 'Pill', 'square' => 'Square'] as $k => $l)<option value="{{ $k }}" @selected($btn['shape'] === $k)>{{ $l }}</option>@endforeach</select>
                            </label>
                            <label class="text-xs text-slate-500">Shadow
                                <select id="bio-btn-shadow" class="lf-input mt-1">@foreach (['none' => 'None', 'sm' => 'Soft', 'lg' => 'Large'] as $k => $l)<option value="{{ $k }}" @selected($btn['shadow'] === $k)>{{ $l }}</option>@endforeach</select>
                            </label>
                            <label class="flex items-center gap-2 self-end text-sm text-slate-600"><input type="checkbox" id="bio-btn-frosted" @checked($btn['frosted'] ?? false) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Frosted glass</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SETTINGS --}}
            <div data-tab="settings" class="hidden" data-settings-pane>
                <div class="lf-card divide-y divide-slate-100">
                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Profile</h3>
                        <label class="text-xs text-slate-500">Bio / tagline
                            <textarea id="bio-description" rows="2" class="lf-input mt-1" placeholder="A short line about you">{{ $s['description'] ?? '' }}</textarea>
                        </label>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-2 self-end text-sm text-slate-600"><input type="checkbox" id="bio-avatar-display" @checked($avatar['display'] ?? true) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Show avatar</label>
                            <label class="text-xs text-slate-500">Avatar style
                                <select id="bio-avatar-style" class="lf-input mt-1">@foreach (['circle' => 'Circle', 'rounded' => 'Rounded', 'square' => 'Square'] as $k => $l)<option value="{{ $k }}" @selected(($avatar['style'] ?? 'circle') === $k)>{{ $l }}</option>@endforeach</select>
                            </label>
                            <div class="sm:col-span-2">
                                <label class="text-xs text-slate-500" for="bio-avatar-image">Avatar image</label>
                                <div class="mt-1 flex gap-2">
                                    <input type="url" id="bio-avatar-image" value="{{ $avatar['image'] ?? '' }}" class="lf-input" placeholder="Paste URL or upload">
                                    <button type="button" data-upload-target="bio-avatar-image" class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Upload</button>
                                </div>
                            </div>
                        </div>
                        <label class="mt-4 flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" id="bio-verified" @checked($s['verified'] ?? false) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Verified badge</label>
                    </div>
                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">SEO</h3>
                        <div class="space-y-3">
                            <label class="block text-xs text-slate-500">Meta title <input type="text" id="bio-seo-title" value="{{ $seo['title'] ?? '' }}" class="lf-input mt-1"></label>
                            <label class="block text-xs text-slate-500">Meta description <textarea id="bio-seo-desc" rows="2" class="lf-input mt-1">{{ $seo['description'] ?? '' }}</textarea></label>
                            <label class="block text-xs text-slate-500">Share image URL <input type="url" id="bio-seo-image" value="{{ $seo['image'] ?? '' }}" class="lf-input mt-1" placeholder="https://..."></label>
                        </div>
                    </div>

                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Privacy &amp; visibility</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="lf-label" for="bio_password">Password protection</label>
                                <input id="bio_password" name="bio_password" type="password" autocomplete="new-password" class="lf-input" placeholder="{{ $hasPassword ? 'Set. Leave blank to keep.' : 'No password' }}">
                                @if ($hasPassword)
                                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="bio_password_remove" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600"> Remove password</label>
                                @endif
                            </div>
                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" id="bio-sensitive" @checked($s['sensitive'] ?? false) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Sensitive content warning before showing the page</label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 {{ $canBrand ? '' : 'opacity-60' }}"><input type="checkbox" id="bio-hide-branding" @checked($s['hide_branding'] ?? false) @disabled(! $canBrand) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Hide "Powered by" branding @unless($canBrand)<span class="text-xs text-slate-400">(paid plans)</span>@endunless</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('bio.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ $page ? 'Save changes' : 'Create bio page' }}</button>
            </div>
        </form>

        {{-- ====== right: live preview ====== --}}
        <div class="lg:sticky lg:top-6 lg:self-start">
            <div class="lf-card p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Live preview</h3>
                    @if ($page)<a href="{{ url('/'.$page->slug) }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:text-brand-700">View bio</a>@endif
                </div>
                <div class="mt-4 flex justify-center">
                    <div class="overflow-hidden rounded-[2rem] border-[6px] border-slate-900 bg-white shadow-xl" style="width:300px;height:600px">
                        <iframe id="bio-preview-frame" class="h-full w-full" title="Bio preview"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/bio-builder.js')
</x-app-layout>
