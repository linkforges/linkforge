@php $current = old('theme_preset', $s['theme_preset'] ?? \App\Support\ThemePalette::DEFAULT_PRESET); @endphp
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="appearance">

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Colour theme</h3>
        <p class="mb-4 text-xs text-slate-400">Re-themes the entire app instantly. No rebuild needed.</p>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($presets as $key => $preset)
                @php $brand = $colors[$preset['brand']]; $spark = $colors[$preset['spark']]; @endphp
                <label @class(['relative flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition', 'border-brand-500 ring-2 ring-brand-500/30' => $current === $key, 'border-slate-200 hover:border-slate-300' => $current !== $key])>
                    <input type="radio" name="theme_preset" value="{{ $key }}" @checked($current === $key) class="peer sr-only">
                    <span class="flex -space-x-1.5">
                        <span class="h-7 w-7 rounded-full ring-2 ring-white" style="background:{{ $brand['500'] }}"></span>
                        <span class="h-7 w-7 rounded-full ring-2 ring-white" style="background:{{ $spark['500'] }}"></span>
                    </span>
                    <span class="text-sm font-medium text-slate-800">{{ $preset['label'] }}</span>
                    <svg class="ml-auto h-4 w-4 text-brand-600 opacity-0 peer-checked:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </label>
            @endforeach
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Default colour scheme</h3>
        <p class="mb-4 text-xs text-slate-400">What new visitors see before they pick their own. Each user can still toggle light/dark.</p>
        @php $scheme = old('theme_scheme', $s['theme_scheme'] ?? config('linkforge.theme.scheme', 'system')); @endphp
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'Match system'] as $val => $lbl)
                <label @class(['flex cursor-pointer items-center gap-2.5 rounded-xl border p-3.5 text-sm font-medium transition', 'border-brand-500 ring-2 ring-brand-500/30 text-brand-700' => $scheme === $val, 'border-slate-200 text-slate-700 hover:border-slate-300' => $scheme !== $val])>
                    <input type="radio" name="theme_scheme" value="{{ $val }}" @checked($scheme === $val) class="sr-only">
                    {{ $lbl }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Typography &amp; logo</h3>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="theme_font">Font</label>
                <select id="theme_font" name="theme_font" class="lf-input">
                    @foreach ($fonts as $font)
                        <option value="{{ $font }}" @selected(old('theme_font', $s['theme_font'] ?? config('linkforge.theme.font')) === $font)>{{ $font }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lf-label">Logo</label>
                @php $logo = $s['brand_logo'] ?? ''; @endphp
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="Current logo" class="h-full w-full object-contain p-1.5">
                        @else
                            <svg class="h-6 w-6 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700">
                        @error('logo_file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-slate-400">PNG, JPG, WebP, GIF or SVG, up to 2&nbsp;MB. Raster logos are auto-resized; shown at a consistent height in the header.</p>
                        @if ($logo)
                            <label class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                <input type="checkbox" name="logo_clear" value="1" class="h-3.5 w-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                                Remove current logo
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <label class="lf-label">Favicon</label>
                @php $favicon = $s['brand_favicon'] ?? ''; @endphp
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        @if ($favicon)
                            <img src="{{ $favicon }}" alt="Current favicon" class="h-8 w-8 object-contain">
                        @else
                            <svg class="h-6 w-6 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <input type="file" name="favicon_file" accept=".ico,.png,.svg,image/png,image/svg+xml,image/x-icon,image/vnd.microsoft.icon" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700">
                        @error('favicon_file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-slate-400">PNG, SVG or ICO, square and 32&times;32 or larger, up to 1&nbsp;MB. Shown in the browser tab across the whole app.</p>
                        @if ($favicon)
                            <label class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                <input type="checkbox" name="favicon_clear" value="1" class="h-3.5 w-3.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                                Remove current favicon
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Custom code</h3>
        <p class="mb-4 text-xs text-slate-400">Injected into the public site and customer dashboard (never the admin panel). For brand tweaks or third-party snippets you trust.</p>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="custom_css">Custom CSS</label>
                <textarea id="custom_css" name="custom_css" rows="5" class="lf-input font-mono text-xs" placeholder=".lf-card { border-radius: 1rem; }">{{ old('custom_css', $s['custom_css'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-400">Wrapped in a &lt;style&gt; tag automatically.</p>
            </div>
            <div>
                <label class="lf-label" for="custom_head">Custom head HTML</label>
                <textarea id="custom_head" name="custom_head" rows="4" class="lf-input font-mono text-xs" placeholder="&lt;link rel=&quot;stylesheet&quot; href=&quot;https://...&quot;&gt;">{{ old('custom_head', $s['custom_head'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-400">Added to the end of &lt;head&gt;: fonts, meta tags or trusted scripts.</p>
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Footer</h3>
        <p class="mb-4 text-xs text-slate-400">The small copyright line on the public site. Leave blank for the default. HTML is allowed (e.g. links); use <code class="rounded bg-slate-100 px-1 text-[11px]">{year}</code> for the current year.</p>
        <input type="text" name="footer_text" value="{{ old('footer_text', $s['footer_text'] ?? '') }}" class="lf-input" placeholder="&copy; {year} Your Brand. All rights reserved.">
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save appearance</button>
    </div>
</form>
