<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="seo">

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Meta</h3>
        <div>
            <label class="lf-label" for="seo_meta_description">Default meta description</label>
            <textarea id="seo_meta_description" name="seo_meta_description" rows="3" class="lf-input" placeholder="{{ config('linkforge.description') }}">{{ old('seo_meta_description', $s['seo_meta_description'] ?? '') }}</textarea>
            <p class="mt-1 text-xs text-slate-400">Used on the marketing site and as the social/SEO fallback.</p>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Analytics</h3>
        <p class="mb-4 text-xs text-slate-400">Tracking code is injected on the public site and dashboard (not the admin panel).</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="lf-label" for="seo_ga_id">Google Analytics ID</label>
                <input id="seo_ga_id" name="seo_ga_id" value="{{ old('seo_ga_id', $s['seo_ga_id'] ?? '') }}" class="lf-input font-mono text-xs" placeholder="G-XXXXXXXXXX">
                @error('seo_ga_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="seo_gtm_id">Google Tag Manager ID</label>
                <input id="seo_gtm_id" name="seo_gtm_id" value="{{ old('seo_gtm_id', $s['seo_gtm_id'] ?? '') }}" class="lf-input font-mono text-xs" placeholder="GTM-XXXXXXX">
                @error('seo_gtm_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save SEO</button>
    </div>
</form>
