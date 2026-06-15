<x-app-layout title="New ticket">
    <x-slot:header>New support ticket</x-slot:header>

    <div class="mb-5">
        <a href="{{ route('support.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to tickets
        </a>
    </div>

    @if ($errors->any())<div class="mx-auto mb-5 max-w-2xl rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">Please fix the errors below.</div>@endif

    <form method="POST" action="{{ route('support.store') }}" class="mx-auto max-w-2xl space-y-5">
        @csrf
        <div class="lf-card space-y-4 p-6">
            <div>
                <label class="lf-label" for="subject">Subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" class="lf-input" placeholder="Briefly, what do you need help with?" required>
                @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="lf-label" for="category">Category</label>
                    <select id="category" name="category" class="lf-input">
                        @foreach ($categories as $k => $l)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="lf-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="lf-input">
                        @foreach ($priorities as $k => $l)<option value="{{ $k }}" @selected(old('priority', 'normal') === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="lf-label" for="message">Message</label>
                <textarea id="message" name="message" rows="6" class="lf-input" placeholder="Describe your issue in detail. Include links, steps, and anything that helps us help you." required>{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('support.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Submit ticket</button>
        </div>
    </form>
</x-app-layout>
