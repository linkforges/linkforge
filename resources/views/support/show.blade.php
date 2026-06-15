<x-app-layout :title="'Ticket #'.$ticket->id">
    <x-slot:header>Ticket #{{ $ticket->id }}</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif

    <div class="mb-5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('support.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                All tickets
            </a>
            <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ $ticket->subject }}</h2>
            <p class="mt-0.5 text-xs text-slate-400">{{ \App\Models\Ticket::CATEGORIES[$ticket->category] ?? 'General' }} · opened {{ $ticket->created_at?->diffForHumans() }}</p>
        </div>
        @include('partials.ticket-status', ['status' => $ticket->status])
    </div>

    <div class="lf-card p-5 sm:p-6">
        @include('partials.ticket-thread', ['ticket' => $ticket, 'selfRole' => 'user'])

        <div class="mt-6 border-t border-slate-100 pt-5">
            @if ($ticket->isClosed())
                <p class="mb-3 text-sm text-slate-500">This ticket is closed. Replying will reopen it.</p>
            @endif
            <form method="POST" action="{{ route('support.reply', $ticket) }}" class="space-y-3">
                @csrf
                <textarea name="message" rows="4" class="lf-input" placeholder="Write a reply..." required>{{ old('message') }}</textarea>
                @error('message')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="flex items-center justify-between gap-3">
                    @unless ($ticket->isClosed())
                        <button type="submit" form="ticket-close" class="text-sm font-medium text-slate-400 transition hover:text-red-600">Close ticket</button>
                    @else
                        <span></span>
                    @endunless
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Send reply</button>
                </div>
            </form>
            <form id="ticket-close" method="POST" action="{{ route('support.close', $ticket) }}" class="hidden">@csrf</form>
        </div>
    </div>
</x-app-layout>
