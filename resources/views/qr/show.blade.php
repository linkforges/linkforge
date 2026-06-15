<x-app-layout title="QR code">
    <x-slot:header>QR code</x-slot:header>

    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('links.index') }}" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Back to links">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <a href="{{ $url }}" target="_blank" rel="noopener" class="font-medium text-brand-700 hover:underline">{{ $link->shortUrl() }}</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[340px_1fr]">
        <div class="lf-card flex flex-col items-center justify-center p-6">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <img id="qr-preview" src="{{ route('links.qr.render', $link) }}" alt="QR code for {{ $link->shortUrl() }}" class="h-56 w-56">
            </div>
            <p class="mt-3 max-w-[16rem] text-center text-xs break-all text-slate-400">{{ $url }}</p>
        </div>

        <div class="lf-card space-y-5 p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="qr-fg" class="lf-label">Foreground</label>
                    <input id="qr-fg" type="color" value="#0f172a" class="h-10 w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
                </div>
                <div>
                    <label for="qr-bg" class="lf-label">Background</label>
                    <input id="qr-bg" type="color" value="#ffffff" class="h-10 w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
                </div>
            </div>

            <div>
                <label for="qr-size" class="lf-label">Size <span id="qr-size-val" class="font-normal text-slate-400">320px</span></label>
                <input id="qr-size" type="range" min="160" max="640" step="20" value="320" class="w-full accent-brand-600">
            </div>

            <div>
                <label class="lf-label">Format</label>
                <div class="inline-flex rounded-lg border border-slate-200 p-1">
                    <button type="button" data-fmt="svg" class="qr-fmt rounded-md bg-brand-50 px-4 py-1.5 text-sm font-medium text-brand-700">SVG</button>
                    <button type="button" data-fmt="png" class="qr-fmt rounded-md px-4 py-1.5 text-sm font-medium text-slate-500">PNG</button>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-1">
                <a id="qr-download" href="#" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Download
                </a>
            </div>

            <p class="text-xs text-slate-400">Scans of this QR code are tracked as clicks on the link.</p>
        </div>
    </div>

    <script>
        (function () {
            const base = @json(route('links.qr.render', $link));
            const fg = document.getElementById('qr-fg');
            const bg = document.getElementById('qr-bg');
            const size = document.getElementById('qr-size');
            const sizeVal = document.getElementById('qr-size-val');
            const preview = document.getElementById('qr-preview');
            const dl = document.getElementById('qr-download');
            let format = 'svg';

            function params(download) {
                const p = new URLSearchParams({ fg: fg.value, bg: bg.value, size: size.value, format: format });
                if (download) p.set('download', '1');
                return p.toString();
            }
            function refresh() {
                sizeVal.textContent = size.value + 'px';
                preview.src = base + '?' + params(false);
                dl.href = base + '?' + params(true);
            }
            [fg, bg, size].forEach(el => el.addEventListener('input', refresh));
            document.querySelectorAll('.qr-fmt').forEach(btn => btn.addEventListener('click', function () {
                format = this.dataset.fmt;
                document.querySelectorAll('.qr-fmt').forEach(b => {
                    const active = b.dataset.fmt === format;
                    b.classList.toggle('bg-brand-50', active);
                    b.classList.toggle('text-brand-700', active);
                    b.classList.toggle('text-slate-500', !active);
                });
                refresh();
            }));
            refresh();
        })();
    </script>
</x-app-layout>
