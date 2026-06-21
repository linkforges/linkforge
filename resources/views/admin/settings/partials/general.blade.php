<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="general">

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Site identity</h3>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="site_name">Site name</label>
                <input id="site_name" name="site_name" value="{{ old('site_name', $s['site_name'] ?? config('linkforge.name')) }}" class="lf-input">
                @error('site_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="site_tagline">Tagline</label>
                <input id="site_tagline" name="site_tagline" value="{{ old('site_tagline', $s['site_tagline'] ?? config('linkforge.tagline')) }}" class="lf-input">
            </div>
            <div>
                <label class="lf-label" for="site_description">Description</label>
                <textarea id="site_description" name="site_description" rows="3" class="lf-input">{{ old('site_description', $s['site_description'] ?? config('linkforge.description')) }}</textarea>
                <p class="mt-1 text-xs text-slate-400">Used for the marketing page and default social/SEO meta.</p>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Localization</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="lf-label" for="app_timezone">Timezone</label>
                @php $currentTz = old('app_timezone', $s['app_timezone'] ?? config('app.timezone', 'UTC')); @endphp
                <select id="app_timezone" name="app_timezone" class="lf-input">
                    @foreach (timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" @selected($currentTz === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">All dates and times are shown in this zone.</p>
            </div>
            <div>
                <label class="lf-label" for="date_format">Date format</label>
                @php $currentFmt = old('date_format', $s['date_format'] ?? config('linkforge.date_format', 'M j, Y')); @endphp
                <select id="date_format" name="date_format" class="lf-input">
                    @foreach (['M j, Y', 'F j, Y', 'd/m/Y', 'm/d/Y', 'Y-m-d', 'd M Y'] as $fmt)
                        <option value="{{ $fmt }}" @selected($currentFmt === $fmt)>{{ date($fmt) }} ({{ $fmt }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">How dates appear across the app.</p>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Access</h3>
        <label class="flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="allow_registration" value="1" @checked(($s['allow_registration'] ?? '1') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>Allow new user registrations<br><span class="text-xs text-slate-400">When off, the sign-up page redirects to login.</span></span>
        </label>
        <label class="mt-4 flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="guest_shorten" value="1" @checked(($s['guest_shorten'] ?? '1') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>Allow guests to shorten links on the homepage<br><span class="text-xs text-slate-400">Anonymous, rate-limited and safety-scanned. A great top-of-funnel hook.</span></span>
        </label>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="lf-label" for="signup_default_plan">Default plan for new signups</label>
                <select id="signup_default_plan" name="signup_default_plan" class="lf-input">
                    <option value="">Free (default)</option>
                    @foreach (\App\Models\Plan::orderBy('id')->get() as $plan)
                        <option value="{{ $plan->id }}" @selected((string) ($s['signup_default_plan'] ?? '') === (string) $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lf-label" for="signup_blocked_domains">Blocked email domains</label>
                <textarea id="signup_blocked_domains" name="signup_blocked_domains" rows="2" class="lf-input font-mono text-xs" placeholder="competitor.com&#10;spam.example">{{ old('signup_blocked_domains', $s['signup_blocked_domains'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-400">One domain per line. Signups from these are rejected.</p>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Maintenance mode</h3>
        <p class="mb-4 text-xs text-slate-400">Shows a maintenance notice on the marketing site and dashboard. Admins, short links, and bio pages keep working.</p>
        <label class="flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="maintenance_mode" value="1" @checked(($s['maintenance_mode'] ?? '0') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>Enable maintenance mode</span>
        </label>
        <div class="mt-4">
            <label class="lf-label" for="maintenance_message">Notice</label>
            <input id="maintenance_message" name="maintenance_message" value="{{ old('maintenance_message', $s['maintenance_message'] ?? '') }}" class="lf-input" placeholder="We'll be back shortly.">
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Announcement banner</h3>
        <p class="mb-4 text-xs text-slate-400">A dismissible message shown across the public site and customer dashboard (not the admin panel). Editing the text re-shows it to everyone.</p>
        <label class="flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="announcement_enabled" value="1" @checked(($s['announcement_enabled'] ?? '0') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>Show the announcement banner</span>
        </label>
        <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_200px]">
            <div>
                <label class="lf-label" for="announcement_text">Message</label>
                <input id="announcement_text" name="announcement_text" value="{{ old('announcement_text', $s['announcement_text'] ?? '') }}" class="lf-input" placeholder="New: bulk import is live. HTML and links allowed.">
            </div>
            <div>
                <label class="lf-label" for="announcement_style">Style</label>
                <select id="announcement_style" name="announcement_style" class="lf-input">
                    @foreach (['info' => 'Info (brand)', 'warning' => 'Warning (amber)', 'success' => 'Success (green)'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(($s['announcement_style'] ?? 'info') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Cookie consent</h3>
        <p class="mb-4 text-xs text-slate-400">A dismissible cookie notice shown to visitors on the public site and dashboard.</p>
        <label class="flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="cookie_consent_enabled" value="1" @checked(($s['cookie_consent_enabled'] ?? '0') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>Show the cookie consent notice</span>
        </label>
        <div class="mt-4">
            <label class="lf-label" for="cookie_consent_text">Notice text</label>
            <input id="cookie_consent_text" name="cookie_consent_text" value="{{ old('cookie_consent_text', $s['cookie_consent_text'] ?? '') }}" class="lf-input" placeholder="We use cookies to improve your experience. HTML and links allowed.">
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save general</button>
    </div>
</form>

<div class="lf-card mt-6 p-6">
    <h3 class="mb-1 text-sm font-semibold text-slate-900">Maintenance tools</h3>
    <p class="mb-4 text-xs text-slate-400">Run common upkeep tasks without shell access. Handy while the cron job is still being set up.</p>
    <div class="flex flex-wrap gap-3">
        @foreach ([
            'clear-cache' => ['Clear caches', 'Config, route, view and application caches'],
            'run-rollup' => ['Run analytics rollup', 'Process new clicks into reports now'],
            'run-queue' => ['Process queued jobs', 'Send pending emails and webhooks now'],
        ] as $action => $meta)
            <form method="POST" action="{{ route('admin.maintenance') }}">
                @csrf
                <input type="hidden" name="action" value="{{ $action }}">
                <button type="submit" title="{{ $meta[1] }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    {{ $meta[0] }}
                </button>
            </form>
        @endforeach
    </div>
</div>
