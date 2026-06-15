<x-admin-layout title="Content">
    <x-slot:header>Content moderation</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif

    {{-- Tabs --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex gap-1">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.moderation', ['tab' => $key]) }}"
                   @class(['rounded-lg px-3.5 py-2 text-sm font-medium transition', 'bg-brand-50 text-brand-700' => $tab === $key, 'text-slate-600 hover:bg-slate-100' => $tab !== $key])>{{ $label }}</a>
            @endforeach
        </nav>
        <form method="GET" class="relative w-full sm:max-w-xs">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input name="q" value="{{ $q }}" class="lf-input pl-9" placeholder="Search...">
        </form>
    </div>

    <div class="lf-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                @if ($tab === 'bio')
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr><th class="px-5 py-3 font-medium">Page</th><th class="px-5 py-3 font-medium">Owner</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Views</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $page)
                            <tr>
                                <td class="px-5 py-3"><span class="font-medium text-slate-900">{{ $page->title ?: '@'.$page->slug }}</span><div class="text-xs text-brand-700">/{{ $page->slug }}</div></td>
                                <td class="px-5 py-3 text-slate-500">{{ $page->user?->email ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $page->is_published ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span></td>
                                <td class="px-5 py-3 text-slate-700">{{ number_format($page->views) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($page->is_published)<a href="{{ url('/'.$page->slug) }}" target="_blank" rel="noopener" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">View</a>@endif
                                        <form method="POST" action="{{ route('admin.moderation.bio.update', $page) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="action" value="{{ $page->is_published ? 'unpublish' : 'publish' }}">
                                            <button type="submit" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">{{ $page->is_published ? 'Unpublish' : 'Publish' }}</button>
                                        </form>
                                        @include('admin.moderation.partials.delete', ['action' => route('admin.moderation.bio.update', $page), 'method' => 'PUT', 'confirm' => 'Delete this bio page?'])
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">No bio pages found.</td></tr>
                        @endforelse
                    </tbody>
                @elseif ($tab === 'qr')
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr><th class="px-5 py-3 font-medium">Name</th><th class="px-5 py-3 font-medium">Owner</th><th class="px-5 py-3 font-medium">Type</th><th class="px-5 py-3 font-medium">Scans</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $qr)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $qr->name ?: 'QR #'.$qr->id }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $qr->user?->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $qr->type }}{{ $qr->is_dynamic ? ' · dynamic' : '' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ number_format($qr->scans) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end">
                                        @include('admin.moderation.partials.delete', ['action' => route('admin.moderation.qr.destroy', $qr), 'method' => 'DELETE', 'confirm' => 'Delete this QR code?'])
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">No QR codes found.</td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr><th class="px-5 py-3 font-medium">Domain</th><th class="px-5 py-3 font-medium">Owner</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Last checked</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $domain)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $domain->host }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $domain->user?->email ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $domain->status === 'active' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($domain->status) }}</span></td>
                                <td class="px-5 py-3 text-slate-500">{{ $domain->last_checked_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($domain->status !== 'active')
                                            <form method="POST" action="{{ route('admin.moderation.domains.update', $domain) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="action" value="verify">
                                                <button type="submit" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">Mark verified</button>
                                            </form>
                                        @endif
                                        @include('admin.moderation.partials.delete', ['action' => route('admin.moderation.domains.update', $domain), 'method' => 'PUT', 'confirm' => 'Delete this domain?'])
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">No custom domains found.</td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $items->links() }}</div>
</x-admin-layout>
