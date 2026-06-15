<x-install-layout title="Database" step="database">
    <h1 class="text-xl font-semibold text-slate-900">Database &amp; site</h1>
    <p class="mt-1 text-sm text-slate-500">Create a MySQL/MariaDB database in cPanel, then enter its details. We'll write your configuration and set up the tables.</p>

    <form method="POST" action="{{ route('install.database.save') }}" class="mt-6 space-y-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="site_name" class="lf-label">Site name</label>
                <input id="site_name" name="site_name" type="text" value="{{ $old['site_name'] }}" required class="lf-input" placeholder="My Links">
                @error('site_name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="app_url" class="lf-label">Site URL</label>
                <input id="app_url" name="app_url" type="url" value="{{ $old['app_url'] }}" required class="lf-input" placeholder="https://example.com">
                @error('app_url')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <hr class="border-slate-100">

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="db_host" class="lf-label">Database host</label>
                <input id="db_host" name="db_host" type="text" value="{{ $old['db_host'] }}" required class="lf-input">
                @error('db_host')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="db_port" class="lf-label">Port</label>
                <input id="db_port" name="db_port" type="number" value="{{ $old['db_port'] }}" required class="lf-input">
                @error('db_port')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="db_database" class="lf-label">Database name</label>
                <input id="db_database" name="db_database" type="text" value="{{ $old['db_database'] }}" required class="lf-input" placeholder="cpaneluser_linkforge">
                @error('db_database')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="db_username" class="lf-label">Database user</label>
                <input id="db_username" name="db_username" type="text" value="{{ $old['db_username'] }}" required class="lf-input" placeholder="cpaneluser_dbuser">
                @error('db_username')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="db_password" class="lf-label">Database password</label>
                <input id="db_password" name="db_password" type="password" class="lf-input" placeholder="••••••••" autocomplete="off">
                @error('db_password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <a href="{{ route('install.welcome') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">&larr; Back</a>
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Test &amp; continue</button>
        </div>
    </form>
</x-install-layout>
