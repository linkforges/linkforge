<x-app-layout title="Support">
    <x-slot:header>Support</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif

    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Need a hand? Open a ticket and our team will get back to you.</p>
        <a href="{{ route('support.create') }}" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            New ticket
        </a>
    </div>

    @if ($tickets->isEmpty())
        <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl text-white" style="background-image:linear-gradient(135deg,var(--color-brand-500),var(--color-brand-700))">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <h3 class="mt-4 text-lg font-semibold text-slate-900">No tickets yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-slate-500">When you need help, open a ticket and track the conversation here.</p>
            <a href="{{ route('support.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Open your first ticket</a>
        </div>
    @else
        <div class="lf-card divide-y divide-slate-100">
            @foreach ($tickets as $t)
                <a href="{{ route('support.show', $t) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50/50">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-900">{{ $t->subject }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">#{{ $t->id }} · {{ \App\Models\Ticket::CATEGORIES[$t->category] ?? 'General' }} · updated {{ $t->last_reply_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2.5">
                        @if ($t->status === 'answered')<span class="h-2 w-2 rounded-full bg-brand-500" title="New reply from support"></span>@endif
                        @include('partials.ticket-status', ['status' => $t->status])
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-5">{{ $tickets->links() }}</div>
    @endif
</x-app-layout>
