@php
    use App\Support\BioThemes;
    $d = BioThemes::resolve($page->theme);
    $font = BioThemes::fontFamily($d['font']);
    $bg = $d['bg'];
    $bgCss = match ($bg['type']) {
        'gradient' => "background:linear-gradient({$bg['gradAngle']}deg, {$bg['gradStart']}, {$bg['gradStop']});",
        'image' => ! empty($bg['image']) ? "background:#0f172a url('{$bg['image']}') center/cover no-repeat fixed;" : 'background:#0f172a;',
        default => "background:{$bg['color']};",
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title ?: '@'.$page->slug }}</title>
    @vite(['resources/css/app.css'])
    <style>body { font-family: {!! $font !!}; {!! $bgCss !!} }</style>
</head>
<body class="flex min-h-screen items-center justify-center px-5">
    <div class="w-full max-w-sm rounded-2xl bg-white/95 p-7 text-center shadow-2xl ring-1 ring-black/5 backdrop-blur">
        @if ($mode === 'password')
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <h1 class="mt-4 text-lg font-bold text-slate-900">This page is protected</h1>
            <p class="mt-1.5 text-sm text-slate-500">Enter the password to continue.</p>
            @if ($error)<p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ $error }}</p>@endif
            <form method="POST" action="{{ route('bio.unlock', $page->slug) }}" class="mt-5 space-y-3">
                @csrf
                <input type="password" name="password" required autofocus class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/20 focus:outline-none" placeholder="Password">
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Unlock</button>
            </form>
        @else
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            </span>
            <h1 class="mt-4 text-lg font-bold text-slate-900">Sensitive content</h1>
            <p class="mt-1.5 text-sm text-slate-500">This page may contain sensitive content. Viewer discretion is advised.</p>
            <form method="POST" action="{{ route('bio.reveal', $page->slug) }}" class="mt-5">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">I understand, continue</button>
            </form>
        @endif
    </div>
</body>
</html>
