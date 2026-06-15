<x-install-layout title="Admin account" step="account">
    <h1 class="text-xl font-semibold text-slate-900">Create your admin account</h1>
    <p class="mt-1 text-sm text-slate-500">This is the owner account you'll use to sign in and manage everything.</p>

    <form method="POST" action="{{ route('install.account.save') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="lf-label">Your name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="lf-input" placeholder="Ada Lovelace">
            @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="lf-label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="lf-input" placeholder="you@example.com" autocomplete="username">
            @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="lf-label">Password</label>
            <input id="password" name="password" type="password" required class="lf-input" placeholder="At least 8 characters" autocomplete="new-password">
            @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="lf-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="lf-input" placeholder="Re-enter your password" autocomplete="new-password">
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <a href="{{ route('install.database') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">&larr; Back</a>
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Continue</button>
        </div>
    </form>
</x-install-layout>
