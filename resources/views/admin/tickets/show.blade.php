<x-admin-layout :title="'Ticket #'.$ticket->id">
    <x-slot:header>Ticket #{{ $ticket->id }}</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif

    <div class="mb-5">
        <a href="{{ route('admin.tickets') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            All tickets
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        {{-- Thread + reply --}}
        <div>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900">{{ $ticket->subject }}</h2>
                @include('partials.ticket-status', ['status' => $ticket->status])
            </div>
            <div class="lf-card p-5 sm:p-6">
                @include('partials.ticket-thread', ['ticket' => $ticket, 'selfRole' => 'admin'])

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="space-y-3">
                        @csrf
                        <textarea name="message" rows="5" class="lf-input" placeholder="Reply to the customer..." required>{{ old('message') }}</textarea>
                        @error('message')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Send reply</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar: customer + controls --}}
        <div class="space-y-6">
            <div class="lf-card p-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Customer</h3>
                <p class="font-medium text-slate-800">{{ $ticket->user?->name ?? 'Unknown' }}</p>
                <p class="text-sm text-slate-500">{{ $ticket->user?->email }}</p>
                <p class="mt-1 text-xs text-slate-400">Plan: {{ $ticket->user?->plan?->name ?? 'Free' }}</p>
                @if ($ticket->user)
                    <a href="{{ route('admin.users.show', $ticket->user) }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700">
                        View customer
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M7 7h10v10"/></svg>
                    </a>
                @endif
            </div>

            <div class="lf-card p-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Manage</h3>
                <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div>
                        <label class="lf-label" for="status">Status</label>
                        <select id="status" name="status" class="lf-input">
                            @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($ticket->status === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lf-label" for="priority">Priority</label>
                        <select id="priority" name="priority" class="lf-input">
                            @foreach ($priorities as $k => $l)<option value="{{ $k }}" @selected($ticket->priority === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Update ticket</button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
