{{-- Renders a ticket's message thread. $ticket (with messages.user loaded) and $selfRole ('user'|'admin'). --}}
<div class="space-y-4">
    @foreach ($ticket->messages as $m)
        @php
            $mine = $m->author_role === $selfRole;
            $label = $m->author_role === 'admin' ? 'Support team' : ($m->user?->name ?: 'Customer');
        @endphp
        <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[85%] sm:max-w-[75%]">
                <div class="mb-1 flex items-center gap-2 text-xs text-slate-400 {{ $mine ? 'justify-end' : '' }}">
                    <span class="font-medium text-slate-600">{{ $mine ? 'You' : $label }}</span>
                    <span>·</span>
                    <span title="{{ $m->created_at }}">{{ $m->created_at?->diffForHumans() }}</span>
                </div>
                <div class="rounded-2xl px-4 py-3 text-sm leading-relaxed {{ $mine ? 'bg-brand-600 text-white' : ($m->author_role === 'admin' ? 'bg-white text-slate-700 ring-1 ring-slate-200' : 'bg-slate-100 text-slate-700') }}">{!! nl2br(e($m->body)) !!}</div>
            </div>
        </div>
    @endforeach
</div>
