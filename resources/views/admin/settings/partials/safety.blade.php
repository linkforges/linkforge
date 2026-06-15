<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="safety">

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Blocklists</h3>
        <p class="mb-4 text-xs text-slate-400">One entry per line. New links matching these are rejected at creation.</p>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="safety_blocked_domains">Blocked domains</label>
                <textarea id="safety_blocked_domains" name="safety_blocked_domains" rows="4" class="lf-input font-mono text-xs" placeholder="known-phish.example&#10;spam.example">{{ old('safety_blocked_domains', $s['safety_blocked_domains'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="lf-label" for="safety_blocked_keywords">Blocked keywords</label>
                <textarea id="safety_blocked_keywords" name="safety_blocked_keywords" rows="3" class="lf-input font-mono text-xs">{{ old('safety_blocked_keywords', $s['safety_blocked_keywords'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Threat feeds</h3>
        <label class="flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="safety_urlhaus" value="1" @checked(($s['safety_urlhaus'] ?? '0') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>URLhaus<br><span class="text-xs text-slate-400">Free abuse.ch malware-URL feed. No API key required.</span></span>
        </label>
        <div class="mt-4 space-y-4">
            @include('admin.settings.partials.secret-field', ['field' => 'safety_virustotal_key', 'label' => 'VirusTotal API key', 'placeholder' => 'Optional'])
            @include('admin.settings.partials.secret-field', ['field' => 'safety_webrisk_key', 'label' => 'Google Web Risk API key', 'placeholder' => 'Optional'])
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">CAPTCHA (Cloudflare Turnstile)</h3>
        <p class="mb-4 text-xs text-slate-400">When both keys are set, registration and link creation are CAPTCHA-protected.</p>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="turnstile_site">Site key</label>
                <input id="turnstile_site" name="turnstile_site" value="{{ old('turnstile_site', $s['turnstile_site'] ?? '') }}" class="lf-input">
            </div>
            @include('admin.settings.partials.secret-field', ['field' => 'turnstile_secret', 'label' => 'Secret key'])
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save safety</button>
    </div>
</form>
