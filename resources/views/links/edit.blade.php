<x-app-layout title="Edit link">
    <x-slot:header>Edit link</x-slot:header>

    <div class="mx-auto max-w-2xl">
        <div class="lf-card p-6 sm:p-8">
            @include('links.partials.form', [
                'action' => route('links.update', $link),
                'method' => 'PUT',
                'submitLabel' => 'Save changes',
                'link' => $link,
                'domain' => $domain,
                'suggestion' => null,
                'pixels' => $pixels,
                'attachedPixelIds' => $attachedPixelIds,
                'aiEnabled' => $aiEnabled,
            ])
        </div>
    </div>
</x-app-layout>
