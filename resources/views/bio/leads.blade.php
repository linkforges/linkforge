<x-app-layout :title="'Leads · '.($page->title ?: $page->slug)">
    <x-slot:header>Leads for {{ $page->title ?: '@'.$page->slug }}</x-slot:header>

    <div class="mb-5">
        <a href="{{ route('bio.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to bio pages
        </a>
    </div>

    {{-- Newsletter subscribers --}}
    <div class="lf-card mb-6 p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Subscribers <span class="ml-1 text-slate-400">({{ $subscribers->total() }})</span></h3>
            @if ($subscribers->total())
                <a href="{{ route('bio.leads.export', [$page, 'type' => 'subscribers']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Export CSV
                </a>
            @endif
        </div>

        @if ($subscribers->isEmpty())
            <p class="py-6 text-center text-sm text-slate-400">No subscribers yet. Add a Newsletter block to your page to start collecting emails.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr><th class="py-2 pr-4 font-medium">Email</th><th class="py-2 pr-4 font-medium">Name</th><th class="py-2 font-medium">Date</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($subscribers as $sub)
                            <tr>
                                <td class="py-2.5 pr-4 font-medium text-slate-800">{{ $sub->email }}</td>
                                <td class="py-2.5 pr-4 text-slate-500">{{ $sub->name ?: '-' }}</td>
                                <td class="py-2.5 text-slate-400">{{ $sub->created_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $subscribers->links() }}</div>
        @endif
    </div>

    {{-- Contact messages --}}
    <div class="lf-card p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Messages <span class="ml-1 text-slate-400">({{ $messages->total() }})</span></h3>
            @if ($messages->total())
                <a href="{{ route('bio.leads.export', [$page, 'type' => 'messages']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Export CSV
                </a>
            @endif
        </div>

        @if ($messages->isEmpty())
            <p class="py-6 text-center text-sm text-slate-400">No messages yet. Add a Contact form block to your page to start receiving messages.</p>
        @else
            <div class="space-y-3">
                @foreach ($messages as $msg)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $msg->name ?: 'Anonymous' }}@if ($msg->email)<span class="ml-2 font-normal text-brand-700">{{ $msg->email }}</span>@endif</p>
                            <span class="text-xs text-slate-400">{{ $msg->created_at->format('M j, Y g:i a') }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $msg->message }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $messages->links() }}</div>
        @endif
    </div>
</x-app-layout>
