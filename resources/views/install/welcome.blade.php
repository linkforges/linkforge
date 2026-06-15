<x-install-layout title="Requirements" step="welcome">
    <h1 class="text-xl font-semibold text-slate-900">Welcome</h1>
    <p class="mt-1 text-sm text-slate-500">Let's get {{ config('linkforge.name') }} set up. First, a quick check that your server is ready.</p>

    <div class="mt-6 space-y-5">
        <div>
            <h2 class="mb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">PHP &amp; extensions</h2>
            <div class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                @foreach ($requirements as $c)
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-slate-700">{{ $c['label'] }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">{{ $c['hint'] }}</span>
                            @if ($c['ok'])
                                <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @else
                                <svg class="h-4 w-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="mb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">Writable paths</h2>
            <div class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                @foreach ($writable as $c)
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="font-mono text-xs text-slate-700">{{ $c['label'] }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">{{ $c['hint'] }}</span>
                            @if ($c['ok'])
                                <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @else
                                <svg class="h-4 w-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-7 flex items-center justify-between gap-3">
        @if ($ready)
            <p class="text-sm text-brand-700">Everything looks good.</p>
            <a href="{{ route('install.database') }}" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Continue</a>
        @else
            <p class="text-sm text-red-600">Please resolve the items marked above, then re-check.</p>
            <div class="flex gap-2">
                <a href="{{ route('install.welcome') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Re-check</a>
                <a href="{{ route('install.database') }}" class="rounded-lg bg-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Continue anyway</a>
            </div>
        @endif
    </div>
</x-install-layout>
