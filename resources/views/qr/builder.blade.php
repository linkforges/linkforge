@php
    $qr = $qr ?? null;
    $boundLink = $boundLink ?? null;
    $boundUrl = $boundLink ? request()->getScheme().'://'.$boundLink->shortUrl() : null;
    $design = $qr->design ?? [];
    $data = $qr->data ?? [];
    $type = $boundLink ? 'link' : ($qr->type ?? 'link');
    $val = fn ($k, $d = '') => e($data[$k] ?? $d);
    $dz = fn ($k, $d) => $design[$k] ?? $d;
    $types = [
        'link' => 'Link', 'text' => 'Text', 'wifi' => 'WiFi', 'vcard' => 'vCard',
        'email' => 'Email', 'sms' => 'SMS', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp',
        'event' => 'Event', 'geo' => 'Location', 'crypto' => 'Crypto',
    ];
    $typeIcons = [
        'link' => '<path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/>',
        'text' => '<path d="M4 7V5h16v2"/><path d="M12 5v14"/><path d="M9 19h6"/>',
        'wifi' => '<path d="M5 12.55a11 11 0 0 1 14 0"/><path d="M8.5 16.43a6 6 0 0 1 7 0"/><path d="M2 8.82a15 15 0 0 1 20 0"/><line x1="12" x2="12.01" y1="20" y2="20"/>',
        'vcard' => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h3M15 12h3M6.5 16a3 3 0 0 1 5 0"/>',
        'email' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'sms' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'whatsapp' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'event' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4M16 2v4"/>',
        'geo' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'crypto' => '<circle cx="12" cy="12" r="10"/><path d="M9.5 9.5h4a1.8 1.8 0 0 1 0 3.6h-4zM9.5 13.1h4.3a1.8 1.8 0 0 1 0 3.6H9.5M9.5 9.5v8M11.2 7.8v1.7M11.2 17.2v1.5"/>',
    ];
@endphp

<x-app-layout :title="$qr ? 'Edit QR code' : 'Create QR code'">
    <x-slot:header>{{ $qr ? 'Edit QR code' : 'Create QR code' }}</x-slot:header>

    @if (session('error'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>
    @endif

    <div id="qr-builder" data-content="{{ $qr->content ?? ($boundUrl ?? 'https://example.com') }}" data-logo="{{ $dz('logo', '') }}"
         class="grid gap-6 lg:grid-cols-[1fr_360px]">

        {{-- ============ FORM (left) ============ --}}
        <form method="POST" action="{{ $action }}" class="space-y-5">
            @csrf
            @if (($method ?? 'POST') !== 'POST') @method($method) @endif

            <input type="hidden" name="type" id="qr-type" value="{{ $type }}">
            <input type="hidden" name="content" id="qr-field-content">
            <input type="hidden" name="data" id="qr-field-data">
            <input type="hidden" name="design" id="qr-field-design">
            @if ($boundLink) <input type="hidden" name="bound_link_id" value="{{ $boundLink->id }}"> @endif

            {{-- Name --}}
            <div class="lf-card p-5">
                <label class="lf-label" for="qr-name">QR code name</label>
                <input id="qr-name" name="name" type="text" value="{{ e($qr->name ?? '') }}" class="lf-input" placeholder="e.g. Spring campaign">
                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror

                @if (! $boundLink)
                    <label class="mt-4 flex items-center gap-2 text-sm text-slate-600" data-dynamic-wrap @class(['hidden' => $type !== 'link'])>
                        <input type="checkbox" name="is_dynamic" id="qr-dynamic" value="1" @checked($qr->is_dynamic ?? true)
                               class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        <span><span class="font-medium text-slate-700">Dynamic.</span> Encode a short link so the destination stays editable and every scan is tracked.</span>
                    </label>
                @endif
            </div>

            {{-- Design templates --}}
            <div class="lf-card p-5" id="qr-templates-card" data-store-url="{{ route('qr.templates.store') }}" data-destroy-base="{{ url('/qr/templates') }}">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Design templates</h3>
                    <button type="button" id="qr-save-template" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 transition hover:text-brand-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                        Save current design
                    </button>
                </div>
                <div id="qr-templates" class="mt-3 flex flex-wrap gap-2">
                    @forelse ($templates ?? [] as $tpl)
                        <span class="group relative inline-flex">
                            <button type="button" data-template-design='@json($tpl->design)'
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-brand-400 hover:bg-brand-50">{{ $tpl->name }}</button>
                            <button type="button" data-template-delete="{{ $tpl->id }}" title="Delete template"
                                    class="absolute -right-1.5 -top-1.5 hidden h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[11px] leading-none text-white group-hover:flex">&times;</button>
                        </span>
                    @empty
                        <p class="text-sm text-slate-400" data-templates-empty>No saved templates yet. Style a code, then choose "Save current design".</p>
                    @endforelse
                </div>
            </div>

            {{-- Content type --}}
            @if ($boundLink)
                <div class="lf-card p-5">
                    <span class="lf-label">Destination</span>
                    <p class="rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">This QR encodes <span class="font-mono">{{ $boundUrl }}</span> and every scan is tracked on the link.</p>
                    <div data-fields="link" class="hidden"><input data-field="url" type="hidden" value="{{ $boundUrl }}"></div>
                </div>
            @else
            <div class="lf-card p-5">
                <span class="lf-label">Content type</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($types as $key => $label)
                        <button type="button" data-type-btn="{{ $key }}" aria-pressed="{{ $type === $key ? 'true' : 'false' }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 aria-pressed:border-brand-500 aria-pressed:bg-brand-50 aria-pressed:text-brand-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $typeIcons[$key] ?? '' !!}</svg>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 space-y-3">
                    {{-- Link --}}
                    <div data-fields="link" @class(['space-y-3', 'hidden' => $type !== 'link'])>
                        <input data-field="url" type="url" class="lf-input" placeholder="https://your-destination.com" value="{{ $val('url') }}">
                    </div>
                    {{-- Text --}}
                    <div data-fields="text" @class(['hidden' => $type !== 'text'])>
                        <textarea data-field="text" rows="3" class="lf-input" placeholder="Any text">{{ $val('text') }}</textarea>
                    </div>
                    {{-- WiFi --}}
                    <div data-fields="wifi" @class(['grid gap-3 sm:grid-cols-2', 'hidden' => $type !== 'wifi'])>
                        <input data-field="ssid" class="lf-input" placeholder="Network name (SSID)" value="{{ $val('ssid') }}">
                        <input data-field="password" class="lf-input" placeholder="Password" value="{{ $val('password') }}">
                        <select data-field="encryption" class="lf-input">
                            @foreach (['WPA' => 'WPA/WPA2', 'WEP' => 'WEP', 'nopass' => 'No password'] as $k => $l)
                                <option value="{{ $k }}" @selected(($data['encryption'] ?? 'WPA') === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                        <label class="flex items-center gap-2 text-sm text-slate-600"><input data-field="hidden" type="checkbox" @checked($data['hidden'] ?? false) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Hidden network</label>
                    </div>
                    {{-- vCard --}}
                    <div data-fields="vcard" @class(['grid gap-3 sm:grid-cols-2', 'hidden' => $type !== 'vcard'])>
                        <input data-field="first_name" class="lf-input" placeholder="First name" value="{{ $val('first_name') }}">
                        <input data-field="last_name" class="lf-input" placeholder="Last name" value="{{ $val('last_name') }}">
                        <input data-field="phone" class="lf-input" placeholder="Phone" value="{{ $val('phone') }}">
                        <input data-field="email" class="lf-input" placeholder="Email" value="{{ $val('email') }}">
                        <input data-field="org" class="lf-input" placeholder="Company" value="{{ $val('org') }}">
                        <input data-field="title" class="lf-input" placeholder="Job title" value="{{ $val('title') }}">
                        <input data-field="website" class="lf-input sm:col-span-2" placeholder="Website" value="{{ $val('website') }}">
                        <input data-field="address" class="lf-input sm:col-span-2" placeholder="Address" value="{{ $val('address') }}">
                    </div>
                    {{-- Email --}}
                    <div data-fields="email" @class(['space-y-3', 'hidden' => $type !== 'email'])>
                        <input data-field="email" type="email" class="lf-input" placeholder="Recipient email" value="{{ $val('email') }}">
                        <input data-field="subject" class="lf-input" placeholder="Subject" value="{{ $val('subject') }}">
                        <textarea data-field="body" rows="2" class="lf-input" placeholder="Message">{{ $val('body') }}</textarea>
                    </div>
                    {{-- SMS --}}
                    <div data-fields="sms" @class(['space-y-3', 'hidden' => $type !== 'sms'])>
                        <input data-field="phone" class="lf-input" placeholder="Phone number" value="{{ $val('phone') }}">
                        <textarea data-field="message" rows="2" class="lf-input" placeholder="Message">{{ $val('message') }}</textarea>
                    </div>
                    {{-- Phone --}}
                    <div data-fields="phone" @class(['hidden' => $type !== 'phone'])>
                        <input data-field="phone" class="lf-input" placeholder="Phone number" value="{{ $val('phone') }}">
                    </div>
                    {{-- WhatsApp --}}
                    <div data-fields="whatsapp" @class(['space-y-3', 'hidden' => $type !== 'whatsapp'])>
                        <input data-field="phone" class="lf-input" placeholder="Phone (with country code)" value="{{ $val('phone') }}">
                        <textarea data-field="message" rows="2" class="lf-input" placeholder="Pre-filled message">{{ $val('message') }}</textarea>
                    </div>
                    {{-- Event --}}
                    <div data-fields="event" @class(['grid gap-3 sm:grid-cols-2', 'hidden' => $type !== 'event'])>
                        <input data-field="summary" class="lf-input sm:col-span-2" placeholder="Event title" value="{{ $val('summary') }}">
                        <input data-field="location" class="lf-input sm:col-span-2" placeholder="Location" value="{{ $val('location') }}">
                        <label class="text-xs text-slate-500">Starts<input data-field="start" type="datetime-local" class="lf-input" value="{{ $val('start') }}"></label>
                        <label class="text-xs text-slate-500">Ends<input data-field="end" type="datetime-local" class="lf-input" value="{{ $val('end') }}"></label>
                    </div>
                    {{-- Location --}}
                    <div data-fields="geo" @class(['grid gap-3 sm:grid-cols-2', 'hidden' => $type !== 'geo'])>
                        <input data-field="lat" class="lf-input" placeholder="Latitude" value="{{ $val('lat') }}">
                        <input data-field="lng" class="lf-input" placeholder="Longitude" value="{{ $val('lng') }}">
                    </div>
                    {{-- Crypto --}}
                    <div data-fields="crypto" @class(['grid gap-3 sm:grid-cols-2', 'hidden' => $type !== 'crypto'])>
                        <select data-field="coin" class="lf-input">
                            @foreach (['bitcoin' => 'Bitcoin', 'ethereum' => 'Ethereum', 'litecoin' => 'Litecoin'] as $k => $l)
                                <option value="{{ $k }}" @selected(($data['coin'] ?? 'bitcoin') === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                        <input data-field="amount" class="lf-input" placeholder="Amount (optional)" value="{{ $val('amount') }}">
                        <input data-field="address" class="lf-input sm:col-span-2" placeholder="Wallet address" value="{{ $val('address') }}">
                    </div>
                </div>
            </div>
            @endif

            {{-- Design --}}
            <div class="lf-card divide-y divide-slate-100">
                {{-- Shape --}}
                <div class="p-5">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Shape</h3>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="text-xs text-slate-500">Body
                            <select id="qr-dots" class="lf-input mt-1">
                                @foreach (['square' => 'Square', 'dots' => 'Dots', 'rounded' => 'Rounded', 'extra-rounded' => 'Extra rounded', 'classy' => 'Classy', 'classy-rounded' => 'Classy rounded'] as $k => $l)
                                    <option value="{{ $k }}" @selected($dz('dotsType', 'square') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-xs text-slate-500">Eye frame
                            <select id="qr-eyeframe" class="lf-input mt-1">
                                @foreach (['square' => 'Square', 'dot' => 'Rounded', 'extra-rounded' => 'Extra rounded'] as $k => $l)
                                    <option value="{{ $k }}" @selected($dz('eyeFrameType', 'square') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-xs text-slate-500">Eye ball
                            <select id="qr-eyeball" class="lf-input mt-1">
                                @foreach (['square' => 'Square', 'dot' => 'Dot'] as $k => $l)
                                    <option value="{{ $k }}" @selected($dz('eyeBallType', 'square') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                {{-- Colors --}}
                <div class="p-5">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Colors</h3>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" id="qr-gradient" @checked($dz('gradient', false)) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Use gradient
                    </label>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs text-slate-500">Foreground <input type="color" id="qr-fg" value="{{ $dz('fg', '#0f172a') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        <label class="text-xs text-slate-500">Background <input type="color" id="qr-bg" value="{{ $dz('bg', '#ffffff') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        <label class="text-xs text-slate-500">Gradient start <input type="color" id="qr-grad-start" value="{{ $dz('gradStart', '#10b981') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        <label class="text-xs text-slate-500">Gradient stop <input type="color" id="qr-grad-stop" value="{{ $dz('gradStop', '#0f766e') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        <label class="text-xs text-slate-500">Gradient type
                            <select id="qr-grad-type" class="lf-input mt-1">
                                <option value="linear" @selected($dz('gradType', 'linear') === 'linear')>Linear</option>
                                <option value="radial" @selected($dz('gradType', 'linear') === 'radial')>Radial</option>
                            </select>
                        </label>
                        <label class="text-xs text-slate-500">Gradient angle <input type="range" id="qr-grad-rotation" min="0" max="360" value="{{ $dz('gradRotation', 0) }}" class="mt-3 block w-full"></label>
                    </div>
                    <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" id="qr-transparent" @checked($dz('transparent', false)) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Transparent background
                    </label>
                    <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" id="qr-eye-custom" @checked($dz('eyeCustom', false)) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Custom eye colors
                    </label>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs text-slate-500">Eye frame <input type="color" id="qr-eyeframe-color" value="{{ $dz('eyeFrameColor', '#0f172a') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        <label class="text-xs text-slate-500">Eye ball <input type="color" id="qr-eyeball-color" value="{{ $dz('eyeBallColor', '#0f172a') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                    </div>
                </div>

                {{-- Frame + CTA --}}
                <div class="p-5">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Frame &amp; call to action</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-xs text-slate-500">Frame
                            <select id="qr-frame" class="lf-input mt-1">
                                @foreach (['none' => 'None', 'bottom' => 'Label (bottom)', 'top' => 'Label (top)', 'box' => 'Bordered box', 'phone' => 'Phone mockup', 'badge' => 'Badge'] as $k => $l)
                                    <option value="{{ $k }}" @selected($dz('frameType', 'none') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-xs text-slate-500">Call to action
                            <input id="qr-cta" type="text" maxlength="20" value="{{ $dz('cta', '') }}" class="lf-input mt-1" placeholder="SCAN ME">
                        </label>
                        <label class="text-xs text-slate-500">Font
                            <select id="qr-frame-font" class="lf-input mt-1">
                                @foreach (['sans' => 'Sans serif', 'serif' => 'Serif', 'mono' => 'Monospace'] as $k => $l)
                                    <option value="{{ $k }}" @selected($dz('frameFont', 'sans') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-xs text-slate-500">Frame color <input type="color" id="qr-frame-color" value="{{ $dz('frameColor', '#0f172a') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                            <label class="text-xs text-slate-500">Text color <input type="color" id="qr-text-color" value="{{ $dz('textColor', '#ffffff') }}" class="mt-1 block h-9 w-full rounded-lg border border-slate-200"></label>
                        </div>
                    </div>
                </div>

                {{-- Logo --}}
                <div class="p-5">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Logo</h3>
                    <p class="mb-2 text-xs text-slate-500">Quick logos</p>
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach (['instagram', 'facebook', 'youtube', 'x', 'tiktok', 'whatsapp', 'spotify', 'github'] as $slug)
                            <button type="button" data-logo-preset="{{ $slug }}" title="{{ ucfirst($slug) }}"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 p-1.5 transition hover:border-brand-400 hover:bg-brand-50">
                                <img src="{{ asset('vendor/social/'.$slug.'.svg') }}" alt="{{ $slug }}" class="h-full w-full">
                            </button>
                        @endforeach
                        <button type="button" data-logo-preset="" class="flex h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-500 transition hover:bg-slate-50">None</button>
                    </div>
                    <input type="file" id="qr-logo-file" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700">
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs text-slate-500">Logo size <input type="range" id="qr-logo-size" min="0.1" max="0.5" step="0.05" value="{{ $dz('logoSize', 0.3) }}" class="mt-2 block w-full"></label>
                        <label class="text-xs text-slate-500">Logo margin <input type="range" id="qr-logo-margin" min="0" max="20" value="{{ $dz('logoMargin', 6) }}" class="mt-2 block w-full"></label>
                    </div>
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" id="qr-hidebg" @checked($dz('hideBgDots', true)) class="h-4 w-4 rounded border-slate-300 text-brand-600"> Clear dots behind logo
                    </label>
                    <button type="button" id="qr-logo-clear" class="mt-3 text-sm font-medium text-slate-500 hover:text-red-600">Remove logo</button>
                </div>

                {{-- Advanced --}}
                <div class="p-5">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Advanced</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-xs text-slate-500">Error correction
                            <select id="qr-ecc" class="lf-input mt-1">
                                @foreach (['L' => 'Low (7%)', 'M' => 'Medium (15%)', 'Q' => 'Quartile (25%)', 'H' => 'High (30%)'] as $k => $l)
                                    <option value="{{ $k }}" @selected($dz('ecc', 'Q') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-xs text-slate-500">Quiet zone <input type="range" id="qr-margin" min="0" max="40" value="{{ $dz('margin', 8) }}" class="mt-3 block w-full"></label>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Using a logo or fancy shapes? Raise error correction to keep the code scannable, and test before sharing.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('qr.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ $qr ? 'Save changes' : 'Save QR code' }}</button>
            </div>
        </form>

        {{-- ============ PREVIEW (right) ============ --}}
        <div class="lg:sticky lg:top-6 lg:self-start">
            <div class="lf-card p-6">
                <h3 class="text-sm font-semibold text-slate-900">Live preview</h3>
                <div id="qr-preview" class="mt-4 flex items-center justify-center rounded-xl bg-[length:16px_16px] p-4"
                     style="background-image:linear-gradient(45deg,#f1f5f9 25%,transparent 25%),linear-gradient(-45deg,#f1f5f9 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#f1f5f9 75%),linear-gradient(-45deg,transparent 75%,#f1f5f9 75%);background-position:0 0,0 8px,8px -8px,-8px 0"></div>
                <p class="mt-4 text-xs font-medium text-slate-500">Download</p>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    @foreach (['png' => 'PNG', 'svg' => 'SVG', 'jpeg' => 'JPG', 'webp' => 'WebP', 'pdf' => 'PDF'] as $ext => $lbl)
                        <button type="button" data-export="{{ $ext }}" class="rounded-lg border border-slate-200 px-2 py-2 text-xs font-semibold text-slate-600 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">{{ $lbl }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/qr-builder.js')
</x-app-layout>
