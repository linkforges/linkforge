@php $user = auth()->user(); @endphp

<form method="POST" action="{{ route('account.profile') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf @method('PUT')

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">Profile photo</h3>
        <p class="mb-4 text-xs text-slate-400">PNG, JPG, GIF or WebP up to 4 MB. It is resized to a square automatically.</p>

        <div class="flex items-center gap-5">
            <span id="avatar-preview" class="flex h-20 w-20 flex-none items-center justify-center overflow-hidden rounded-full text-xl font-semibold text-white"
                  style="background-image:linear-gradient(135deg,var(--color-brand-500),var(--color-brand-700))">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="Avatar" class="h-full w-full object-cover">
                @else
                    {{ $user->initials() }}
                @endif
            </span>

            <div class="space-y-2">
                <label class="inline-flex cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <span>Choose image</span>
                    <input id="avatar-input" type="file" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp" class="sr-only">
                </label>
                @if ($user->avatarUrl())
                    <label class="flex items-center gap-2 text-xs text-slate-500">
                        <input type="checkbox" name="remove_avatar" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        Remove current photo
                    </label>
                @endif
                <p id="avatar-filename" class="text-xs text-slate-400"></p>
            </div>
        </div>
        @error('avatar')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Profile information</h3>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="lf-input" required>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="lf-input" required>
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save profile</button>
    </div>
</form>

{{-- Danger zone --}}
<div class="lf-card mt-6 border border-red-200 p-6">
    <h3 class="mb-1 text-sm font-semibold text-red-700">Delete account</h3>
    <p class="mb-4 text-xs text-slate-500">Permanently delete your account and all of its links, QR codes, bio pages and other content. This cannot be undone.</p>

    <form method="POST" action="{{ route('account.destroy') }}" class="flex flex-wrap items-end gap-3"
          data-confirm="This permanently deletes your account and everything in it. This cannot be undone." data-confirm-ok="Delete my account">
        @csrf @method('DELETE')
        <div class="min-w-[14rem] flex-1">
            <label class="lf-label" for="delete_password">Confirm your password</label>
            <input id="delete_password" name="password" type="password" class="lf-input" autocomplete="current-password" required>
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">Delete account</button>
    </form>
</div>

<script>
    (function () {
        var input = document.getElementById('avatar-input');
        if (!input) return;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            document.getElementById('avatar-filename').textContent = file.name;
            var reader = new FileReader();
            reader.onload = function (e) {
                var box = document.getElementById('avatar-preview');
                box.innerHTML = '<img src="' + e.target.result + '" alt="Avatar preview" class="h-full w-full object-cover">';
            };
            reader.readAsDataURL(file);
            var remove = document.querySelector('input[name="remove_avatar"]');
            if (remove) remove.checked = false;
        });
    })();
</script>
