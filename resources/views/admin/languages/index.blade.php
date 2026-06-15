<x-admin-layout title="Languages">
    <x-slot:header>Languages</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

    <div class="grid max-w-4xl gap-6">

        {{-- Intro --}}
        <div class="lf-card p-6">
            <h2 class="text-sm font-semibold text-slate-900">Translate your site</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                The interface ships in English. Add a language to translate every label, button and message into it.
                Anything you leave blank falls back to English automatically, so a half-finished translation is safe to use.
                There {{ $total === 1 ? 'is' : 'are' }} <span class="font-medium text-slate-700">{{ number_format($total) }}</span>
                translatable {{ \Illuminate\Support\Str::plural('string', $total) }} in this build.
            </p>
        </div>

        {{-- Default language --}}
        <div class="lf-card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Default language</h2>
                    <p class="mt-1 text-sm text-slate-500">Shown to visitors and new users before they pick their own.</p>
                </div>
                <form method="POST" action="{{ route('admin.languages.default') }}" class="flex items-center gap-2">
                    @csrf
                    <select name="default_locale" class="lf-input h-10 w-48 text-sm">
                        @foreach ($available as $code => $name)
                            <option value="{{ $code }}" @selected($default === $code)>{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-10 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white transition hover:bg-brand-700">Save</button>
                </form>
            </div>
        </div>

        {{-- Installed languages --}}
        <div class="lf-card overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Installed languages</h2>
                <form method="POST" action="{{ route('admin.languages.scan') }}"
                      data-confirm="Re-scan the source code for translatable text? This refreshes the English key list."
                      data-confirm-ok="Rescan">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
                        Rescan strings
                    </button>
                </form>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach ($locales as $locale)
                    <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-900">{{ $locale['name'] }}</span>
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-500">{{ $locale['code'] }}</span>
                                @if ($default === $locale['code'])<span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">Default</span>@endif
                                @if ($locale['source'])<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">Source</span>@endif
                                @if ($locale['rtl'])<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">RTL</span>@endif
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 w-40 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $locale['percent'] === 100 ? 'bg-brand-500' : 'bg-amber-400' }}" style="width: {{ $locale['percent'] }}%"></div>
                                </div>
                                <span class="text-xs text-slate-500">
                                    @if ($locale['source'])
                                        Source language
                                    @else
                                        {{ number_format($locale['translated']) }} / {{ number_format($locale['total']) }} translated ({{ $locale['percent'] }}%)
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @unless ($locale['source'])
                                <a href="{{ route('admin.languages.edit', $locale['code']) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Translate</a>
                            @endunless
                            <a href="{{ route('admin.languages.export', $locale['code']) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Export</a>
                            @unless ($locale['source'])
                                <form method="POST" action="{{ route('admin.languages.destroy', $locale['code']) }}"
                                      data-confirm="Remove {{ $locale['name'] }}? Its translations will be deleted."
                                      data-confirm-ok="Remove language">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Remove">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Add a language --}}
        <div class="lf-card p-6">
            <h2 class="text-sm font-semibold text-slate-900">Add a language</h2>
            <p class="mt-1 text-sm text-slate-500">Pick from the list or type any IETF code (e.g. <span class="font-mono text-xs">fr</span>, <span class="font-mono text-xs">pt-BR</span>, <span class="font-mono text-xs">zh</span>).</p>
            <form method="POST" action="{{ route('admin.languages.store') }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                @csrf
                <input list="lf-locale-suggestions" name="code" required placeholder="Language code"
                       class="lf-input h-10 w-full text-sm sm:w-64" autocomplete="off" spellcheck="false">
                <datalist id="lf-locale-suggestions">
                    @foreach ($addable as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </datalist>
                <button type="submit" class="h-10 shrink-0 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white transition hover:bg-brand-700">Add language</button>
            </form>
        </div>

    </div>
</x-admin-layout>
