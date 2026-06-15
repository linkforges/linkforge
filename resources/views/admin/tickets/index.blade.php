<x-admin-layout title="Support">
    <x-slot:header>Support tickets</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1.5 text-xs">
            <a href="{{ route('admin.tickets') }}" @class(['rounded-md px-2.5 py-1 font-medium', 'bg-slate-900 text-white' => ! $status, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => $status])>All</a>
            @foreach ($statuses as $key => $label)
                <a href="{{ route('admin.tickets', ['status' => $key]) }}" @class(['rounded-md px-2.5 py-1 font-medium', 'bg-slate-900 text-white' => $status === $key, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => $status !== $key])>{{ $label }}@if ($key === 'open' && $openCount) ({{ $openCount }})@endif</a>
            @endforeach
        </div>
        <form method="GET" class="relative w-full sm:max-w-xs">
            <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="q" value="{{ $q }}" class="lf-input pl-9" placeholder="Search subject or customer...">
        </form>
    </div>

    <div class="lf-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                    <tr>
                        <th class="px-5 py-3 font-medium">Subject</th>
                        <th class="px-5 py-3 font-medium">Customer</th>
                        <th class="px-5 py-3 font-medium">Priority</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Updated</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tickets as $t)
                        @php $pb = ['high' => 'text-red-600', 'normal' => 'text-slate-500', 'low' => 'text-slate-400'][$t->priority] ?? 'text-slate-500'; @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.tickets.show', $t) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $t->subject }}</a>
                                <div class="text-xs text-slate-400">#{{ $t->id }} · {{ \App\Models\Ticket::CATEGORIES[$t->category] ?? 'General' }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $t->user?->email ?? '—' }}</td>
                            <td class="px-5 py-3"><span class="font-medium capitalize {{ $pb }}">{{ $t->priority }}</span></td>
                            <td class="px-5 py-3">@include('partials.ticket-status', ['status' => $t->status])</td>
                            <td class="px-5 py-3 text-slate-500">{{ $t->last_reply_at?->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.tickets.show', $t) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">No tickets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $tickets->links() }}</div>
</x-admin-layout>
