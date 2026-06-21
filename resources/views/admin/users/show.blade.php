<x-admin-layout :title="'User: '.$user->name">
    <x-slot:header>{{ $user->name }}</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">Please fix the errors below.</div>@endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            All users
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('admin.users.reset-link', $user) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Send reset link</button>
            </form>
            @if ($impersonatable)
                <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Impersonate</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Identity --}}
    <div class="lf-card mb-6 flex flex-wrap items-center gap-4 p-5">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-lg font-bold text-slate-500">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
        <div class="min-w-0">
            <p class="flex items-center gap-2 font-semibold text-slate-900">{{ $user->email }}
                @if ($user->email_verified_at)
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-brand-700"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>verified</span>
                @else
                    <span class="text-xs font-medium text-amber-600">unverified</span>
                @endif
            </p>
            <p class="text-xs text-slate-400">Joined {{ $user->created_at?->format('M j, Y') }} · {{ $user->created_at?->diffForHumans() }}</p>
        </div>
    </div>

    {{-- Usage --}}
    <div class="mb-6 grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-5">
        @foreach ([['Links', $user->links_count], ['Bio pages', $user->bio_pages_count], ['QR codes', $user->qr_codes_count], ['Domains', $user->domains_count], ['Total clicks', $clicks]] as [$label, $val])
            <div class="lf-card p-4">
                <span class="text-xs font-medium text-slate-500">{{ $label }}</span>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ number_format($val) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        {{-- Edit form --}}
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="lf-card space-y-4 p-6">
            @csrf @method('PUT')
            <h3 class="text-sm font-semibold text-slate-900">Account</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="lf-label" for="name">Name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" class="lf-input" required>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lf-label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="lf-input" required>
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lf-label" for="status">Status</label>
                    <select id="status" name="status" class="lf-input">
                        @foreach (['active', 'suspended', 'pending'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $user->status) === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lf-label" for="role">Role</label>
                    <select id="role" name="role" class="lf-input">
                        <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    </select>
                </div>
                <div>
                    <label class="lf-label" for="plan_id">Plan</label>
                    <select id="plan_id" name="plan_id" class="lf-input">
                        <option value="">Free</option>
                        @foreach ($plans as $p)
                            <option value="{{ $p->id }}" @selected((int) old('plan_id', $user->plan_id) === $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lf-label" for="ai_credits">AI credits</label>
                    <input id="ai_credits" name="ai_credits" type="number" min="0" value="{{ old('ai_credits', $user->ai_credits) }}" class="lf-input">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="verified" value="1" @checked(old('verified', (bool) $user->email_verified_at)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                Email verified
            </label>
            <div class="flex justify-end pt-1">
                <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save changes</button>
            </div>
        </form>

        {{-- Side: billing + danger --}}
        <div class="space-y-6">
            <div class="lf-card p-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Subscriptions</h3>
                @forelse ($subscriptions as $sub)
                    <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                        <span class="text-slate-700">{{ $sub->plan?->name ?? '—' }}</span>
                        <span class="text-xs text-slate-400 capitalize">{{ str_replace('_', ' ', $sub->status) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No subscriptions.</p>
                @endforelse
            </div>

            <div class="lf-card p-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Payments</h3>
                @forelse ($payments as $payment)
                    <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                        <span class="text-slate-700">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</span>
                        <span class="text-xs text-slate-400 capitalize">{{ $payment->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No payments.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.users.email', $user) }}" class="lf-card space-y-3 p-5"
                  data-confirm="Send this email to {{ $user->email }}?" data-confirm-ok="Send email">
                @csrf
                <h3 class="text-sm font-semibold text-slate-900">Email this user</h3>
                <div>
                    <input name="subject" value="{{ old('subject') }}" required class="lf-input" placeholder="Subject">
                    @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <textarea name="message" rows="4" required class="lf-input" placeholder="Your message. Basic HTML is supported.">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Send email</button>
            </form>

            <div class="rounded-xl border border-red-200 bg-red-50/40 p-5">
                <h3 class="text-sm font-semibold text-red-800">Danger zone</h3>
                <p class="mt-1 text-xs text-red-600/80">Permanently deletes this user and all their links, bio pages, QR codes and domains.</p>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-3" data-confirm="Delete {{ $user->email }} and ALL their content? This cannot be undone." data-confirm-ok="Delete user">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg border border-red-300 bg-white px-3.5 py-2 text-sm font-medium text-red-700 transition hover:bg-red-600 hover:text-white">Delete user</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Recent links --}}
    @if ($recentLinks->isNotEmpty())
        <div class="lf-card mt-6 overflow-hidden">
            <h3 class="border-b border-slate-100 px-5 py-3 text-sm font-semibold text-slate-900">Recent links</h3>
            <div class="divide-y divide-slate-100">
                @foreach ($recentLinks as $link)
                    <div class="flex items-center justify-between px-5 py-2.5 text-sm">
                        <div class="min-w-0">
                            <span class="font-medium text-slate-800">/{{ $link->alias }}</span>
                            <span class="ml-2 truncate text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($link->long_url, 60) }}</span>
                        </div>
                        <span class="shrink-0 text-xs text-slate-500">{{ number_format($link->clicks) }} clicks</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-admin-layout>
