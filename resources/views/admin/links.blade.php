<x-admin-layout title="Links">
    <x-slot:header>Links moderation</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif

    <form method="GET" class="relative mb-5 w-full sm:max-w-xs">
        <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input name="q" value="{{ $q }}" class="lf-input pl-9" placeholder="Search links...">
    </form>

    <div class="lf-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                    <tr>
                        <th class="px-5 py-3 font-medium">Alias</th>
                        <th class="px-5 py-3 font-medium">Destination</th>
                        <th class="px-5 py-3 font-medium">Owner</th>
                        <th class="px-5 py-3 font-medium">Safety</th>
                        <th class="px-5 py-3 font-medium">Clicks</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($links as $link)
                        @php $sb = ['safe' => 'bg-brand-50 text-brand-700', 'pending' => 'bg-slate-100 text-slate-500', 'flagged' => 'bg-amber-50 text-amber-700', 'blocked' => 'bg-red-50 text-red-700'][$link->safety_status] ?? 'bg-slate-100 text-slate-500'; @endphp
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $link->alias }}</td>
                            <td class="max-w-xs px-5 py-3"><span class="block truncate text-slate-500" title="{{ $link->long_url }}">{{ $link->long_url }}</span></td>
                            <td class="px-5 py-3 text-slate-500">{{ $link->user?->email ?? '—' }}</td>
                            <td class="px-5 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sb }}">{{ ucfirst($link->safety_status) }}</span></td>
                            <td class="px-5 py-3 text-slate-700">{{ number_format($link->clicks) }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <form method="POST" action="{{ route('admin.links.update', $link) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="{{ $link->safety_status === 'blocked' ? 'unblock' : 'block' }}">
                                        <button type="submit" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50">{{ $link->safety_status === 'blocked' ? 'Unblock' : 'Block' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.links.update', $link) }}" data-confirm="Delete this link?" data-confirm-ok="Delete link">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Delete">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $links->links() }}</div>
</x-admin-layout>
