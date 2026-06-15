<x-app-layout title="Bulk QR import">
    <x-slot:header>Bulk QR import</x-slot:header>

    @if (session('error'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>
    @endif

    <div class="mx-auto max-w-xl">
        <div class="lf-card p-6">
            <p class="text-sm text-slate-500">Generate many <span class="font-medium text-slate-700">dynamic, tracked</span> QR codes at once. One row per code: <code class="text-slate-600">url, name</code> (name optional). A header row is skipped automatically.</p>

            <form method="POST" action="{{ route('qr.bulk.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="lf-label" for="csv">CSV file</label>
                    <input id="csv" type="file" name="csv" accept=".csv,text/csv" required
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700">
                    @error('csv') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lf-label" for="template_id">Design template <span class="font-normal text-slate-400">(optional)</span></label>
                    <select id="template_id" name="template_id" class="lf-input">
                        <option value="">Default (plain black)</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-400">Save a styled design as a template in the builder to apply your branding to every generated code.</p>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('qr.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Generate codes</button>
                </div>
            </form>

            <div class="mt-5 rounded-lg bg-slate-50 p-4 text-xs text-slate-500">
                Example CSV:
                <pre class="mt-1.5 font-mono text-slate-600">url,name
https://example.com/spring, Spring sale
https://example.com/promo, Promo flyer</pre>
            </div>
        </div>
    </div>
</x-app-layout>
