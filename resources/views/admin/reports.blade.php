<x-admin-layout title="Abuse reports">
    <x-slot:header>Abuse reports</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif

    @if ($reports->isEmpty())
        <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m5 13 4 4L19 7"/></svg>
            </span>
            <h3 class="mt-4 text-lg font-semibold text-slate-900">No reports</h3>
            <p class="mt-1.5 text-sm text-slate-500">Abuse reports from the public report form appear here.</p>
        </div>
    @else
        <div class="lf-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-medium">Reason</th>
                            <th class="px-5 py-3 font-medium">Link</th>
                            <th class="px-5 py-3 font-medium">Reporter</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reports as $report)
                            @php $rb = ['open' => 'bg-amber-50 text-amber-700', 'reviewing' => 'bg-slate-100 text-slate-500', 'actioned' => 'bg-brand-50 text-brand-700', 'dismissed' => 'bg-slate-100 text-slate-500'][$report->status] ?? 'bg-slate-100 text-slate-500'; @endphp
                            <tr>
                                <td class="max-w-xs px-5 py-3"><span class="block truncate text-slate-700" title="{{ $report->reason }}">{{ $report->reason }}</span></td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $report->link?->alias ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $report->reporter_email ?: 'Anonymous' }}</td>
                                <td class="px-5 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $rb }}">{{ ucfirst($report->status) }}</span></td>
                                <td class="px-5 py-3 text-right">
                                    @if ($report->status === 'open')
                                        <div class="inline-flex items-center gap-1">
                                            @if ($report->link)
                                                <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                                    @csrf @method('PUT')
                                                    <input type="hidden" name="action" value="block">
                                                    <button type="submit" class="rounded-md bg-red-600 px-2.5 py-1 text-xs font-medium text-white transition hover:bg-red-700">Block link</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="action" value="dismiss">
                                                <button type="submit" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Dismiss</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">{{ $reports->links() }}</div>
    @endif
</x-admin-layout>
