<x-app-layout title="QR Codes">
    <x-slot:header>QR Codes</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-slate-500">Design branded QR codes for links, WiFi, vCards and more.</p>
        <div class="flex items-center gap-2">
            <a href="{{ route('qr.bulk') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                Bulk import
            </a>
            <a href="{{ route('qr.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                Create QR code
            </a>
        </div>
    </div>

    @if ($codes->isEmpty())
        <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h.01M20 14v.01M14 20h6v-6"/></svg>
            </span>
            <h3 class="mt-4 text-lg font-semibold text-slate-900">No QR codes yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-slate-500">Create a branded, styled QR code with your logo, colors and a tracked destination.</p>
            <a href="{{ route('qr.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Create your first QR code</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($codes as $code)
                @php $scans = $code->is_dynamic ? (int) ($code->link?->clicks ?? 0) : (int) $code->scans; @endphp
                <div class="lf-card flex flex-col p-4">
                    <div class="flex items-center justify-center rounded-xl bg-slate-50 p-4">
                        <div class="qr-thumb h-32 w-32" data-config='@json(['content' => $code->content, 'design' => $code->design])'></div>
                    </div>
                    <div class="mt-3 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $code->name ?: ucfirst($code->type).' QR' }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 capitalize">{{ $code->type }}</span>
                                @if ($code->is_dynamic)
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700">Dynamic · {{ number_format($scans) }} scans</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Static</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                        <a href="{{ route('qr.edit', $code) }}" class="flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50">Edit</a>
                        <a href="{{ route('qr.stats', $code) }}" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" title="Analytics">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 14l3-3 3 3 5-6"/></svg>
                        </a>
                        <form method="POST" action="{{ route('qr.destroy', $code) }}" data-confirm="Delete this QR code?" data-confirm-ok="Delete QR code">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Delete">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $codes->links() }}</div>
    @endif

    @vite('resources/js/qr-builder.js')
</x-app-layout>
