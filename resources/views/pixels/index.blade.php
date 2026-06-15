<x-app-layout title="Retargeting pixels">
    <x-slot:header>Retargeting pixels</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div>
            @if ($pixels->isEmpty())
                <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">No pixels yet</h3>
                    <p class="mt-1.5 max-w-sm text-sm text-slate-500">Add a retargeting pixel, then attach it to any link to build audiences from your clicks.</p>
                </div>
            @else
                <div class="lf-card divide-y divide-slate-100">
                    @foreach ($pixels as $px)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-900 capitalize">{{ $px->provider }}</span>
                                    @if ($px->name)<span class="text-xs text-slate-400">{{ $px->name }}</span>@endif
                                </div>
                                <div class="truncate font-mono text-xs text-slate-400">{{ $px->pixel_id }}</div>
                            </div>
                            <form method="POST" action="{{ route('pixels.destroy', $px) }}" data-confirm="Remove this pixel?" data-confirm-ok="Remove pixel">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Remove" aria-label="Remove">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lf-card p-6">
            <h3 class="text-sm font-semibold text-slate-900">Add a pixel</h3>
            <form method="POST" action="{{ route('pixels.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="provider" class="lf-label">Provider</label>
                    <select id="provider" name="provider" class="lf-input">
                        @foreach (['facebook' => 'Facebook / Meta', 'google' => 'Google Ads', 'gtm' => 'Google Tag Manager', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn', 'twitter' => 'X (Twitter)', 'pinterest' => 'Pinterest', 'reddit' => 'Reddit', 'snapchat' => 'Snapchat', 'quora' => 'Quora', 'bing' => 'Microsoft / Bing'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('provider') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('provider') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="pixel_id" class="lf-label">Pixel ID</label>
                    <input id="pixel_id" name="pixel_id" value="{{ old('pixel_id') }}" class="lf-input" placeholder="e.g. 123456789012345">
                    @error('pixel_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="name" class="lf-label">Name <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="name" name="name" value="{{ old('name') }}" class="lf-input" placeholder="Main Meta pixel">
                </div>
                <button type="submit" class="lf-btn">Add pixel</button>
            </form>
        </div>
    </div>
</x-app-layout>
