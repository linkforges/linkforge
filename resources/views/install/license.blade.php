<x-install-layout title="License" step="license">
    <h1 class="text-xl font-semibold text-slate-900">Activate your license</h1>
    <p class="mt-1 text-sm text-slate-500">Enter the purchase code from your CodeCanyon download to verify your copy. You can find it under Downloads &rarr; the item's "License certificate &amp; purchase code".</p>

    <form method="POST" action="{{ route('install.license.save') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="purchase_code" class="lf-label">Envato purchase code</label>
            <input id="purchase_code" name="purchase_code" type="text" value="{{ old('purchase_code') }}" class="lf-input font-mono" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" autocomplete="off" spellcheck="false" required>
            @error('purchase_code')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1.5 text-xs text-slate-400">Format: a code with dashes, e.g. 8f3c9d21-1a2b-4c5d-9e8f-0a1b2c3d4e5f. A valid purchase code is required to complete installation.</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-1">
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Verify &amp; finish</button>
        </div>
    </form>
</x-install-layout>
