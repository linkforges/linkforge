<x-admin-layout title="Broadcast">
    <x-slot:header>Broadcast email</x-slot:header>

    <div class="mx-auto max-w-2xl">
        @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

        <div class="lf-card p-6">
            <p class="mb-5 text-sm text-slate-500">Send a one-off email to your users. Messages are queued and delivered as the cron-driven queue drains, so a large list never blocks.</p>

            <form method="POST" action="{{ route('admin.broadcast.send') }}" class="space-y-5"
                  data-confirm="Send this email to your users? This cannot be undone." data-confirm-ok="Send broadcast">
                @csrf
                <div>
                    <label class="lf-label" for="subject">Subject</label>
                    <input id="subject" name="subject" value="{{ old('subject') }}" required class="lf-input" placeholder="A quick update from {{ config('linkforge.name') }}">
                    @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lf-label" for="message">Message</label>
                    <textarea id="message" name="message" rows="8" required class="lf-input" placeholder="Write your message. Basic HTML is supported.">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-slate-400">Wrapped in your branded email template when sent.</p>
                </div>
                <div>
                    <span class="lf-label">Audience</span>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="radio" name="audience" value="all" checked class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500/30">
                            All users <span class="text-slate-400">({{ number_format($userCount) }})</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="radio" name="audience" value="plan" class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500/30">
                            Only users on plan
                            <select name="plan_id" class="ml-1 rounded-lg border border-slate-300 px-2 py-1 text-sm">
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @error('audience')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Send broadcast</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
