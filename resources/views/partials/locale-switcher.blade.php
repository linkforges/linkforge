{{-- Language switcher. Renders only when more than one UI locale is installed. --}}
@php $lfLocales = \App\Support\Locales::available(); @endphp
@if (count($lfLocales) > 1)
    <details class="relative">
        <summary class="flex h-9 cursor-pointer list-none items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 [&::-webkit-details-marker]:hidden">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>
            <span class="hidden sm:block">{{ $lfLocales[app()->getLocale()] ?? strtoupper(app()->getLocale()) }}</span>
        </summary>
        <div class="absolute right-0 z-30 mt-2 max-h-72 w-44 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
            @foreach ($lfLocales as $code => $name)
                <a href="{{ route('locale.switch', $code) }}"
                   @class(['block px-4 py-2 text-sm transition', 'font-semibold text-brand-700' => app()->getLocale() === $code, 'text-slate-600 hover:bg-slate-50' => app()->getLocale() !== $code])>{{ $name }}</a>
            @endforeach
        </div>
    </details>
@endif
