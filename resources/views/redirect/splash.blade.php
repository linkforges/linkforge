<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting…</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme')
    @foreach ($pixels as $pixel)
        @include('redirect.partials.pixel', ['pixel' => $pixel])
    @endforeach
    <meta http-equiv="refresh" content="3;url={{ $target }}">
</head>
<body class="min-h-screen bg-slate-50">
    <div class="flex min-h-screen flex-col items-center justify-center p-6 text-center">
        <x-application-logo size="h-10 w-10" />
        <div class="mt-6 h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-brand-600"></div>
        <p class="mt-4 text-sm text-slate-500">Taking you to your destination…</p>
        <a href="{{ $target }}" class="mt-4 text-sm font-medium text-brand-600 transition hover:text-brand-700">Continue now</a>
    </div>
    <script>setTimeout(function () { window.location.href = @json($target); }, 1500);</script>
</body>
</html>
