<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('linkforge.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme')
    @include('partials.head-extra')
</head>
<body class="min-h-screen bg-slate-50">
    <div class="flex min-h-screen flex-col items-center justify-center p-6">
        <a href="/" class="mb-8 flex items-center gap-2.5">
            <x-application-logo size="h-9 w-9" />
            <span class="text-lg font-semibold text-slate-900">{{ config('linkforge.name') }}</span>
        </a>
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
