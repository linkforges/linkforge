<x-public-layout title="Report a link">
    <div class="lf-card p-8">
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            </div>
            <h1 class="text-xl font-semibold text-slate-900">Report a link</h1>
            <p class="mt-2 text-sm text-slate-500">Found a malicious or abusive link? Tell us and our team will investigate.</p>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('report.store') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="alias" class="lf-label">Short link code</label>
                <input id="alias" name="alias" value="{{ old('alias', $alias) }}" class="lf-input" placeholder="e.g. abc123">
            </div>
            <div>
                <label for="reporter_email" class="lf-label">Your email <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="reporter_email" name="reporter_email" type="email" value="{{ old('reporter_email') }}" class="lf-input" placeholder="you@example.com">
            </div>
            <div>
                <label for="reason" class="lf-label">What's wrong with this link?</label>
                <textarea id="reason" name="reason" rows="4" class="lf-input" placeholder="Describe the problem" required>{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="hidden" aria-hidden="true">
                <label>Company <input type="text" name="company" tabindex="-1" autocomplete="off"></label>
            </div>
            <button type="submit" class="lf-btn">Submit report</button>
        </form>
    </div>
</x-public-layout>
