<x-admin-layout title="Audit log">
    <x-slot:header>Audit log</x-slot:header>

    <p class="mb-5 text-sm text-slate-500">A record of administrative actions across the panel.</p>

    <div class="lf-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                    <tr>
                        <th class="px-5 py-3 font-medium">When</th>
                        <th class="px-5 py-3 font-medium">Admin</th>
                        <th class="px-5 py-3 font-medium">Action</th>
                        <th class="px-5 py-3 font-medium">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500" title="{{ $log->created_at }}">{{ $log->created_at?->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-5 py-3"><span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600">{{ $log->action }}</span></td>
                            <td class="px-5 py-3 text-slate-600">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">No admin actions recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $logs->links() }}</div>
</x-admin-layout>
