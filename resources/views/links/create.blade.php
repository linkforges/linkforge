<x-app-layout title="Create link">
    <x-slot:header>Create link</x-slot:header>

    <div class="mx-auto max-w-2xl">
        @if (session('error'))
            <div class="mb-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('error') }}</div>
        @endif

        <div class="lf-card p-6 sm:p-8">
            @include('links.partials.form', [
                'action' => route('links.store'),
                'method' => 'POST',
                'submitLabel' => 'Create link',
                'link' => null,
                'domain' => $domain,
                'suggestion' => $suggestion,
                'pixels' => $pixels,
                'attachedPixelIds' => $attachedPixelIds,
                'aiEnabled' => $aiEnabled,
            ])
        </div>
    </div>
</x-app-layout>
