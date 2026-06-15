<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance · {{ config('linkforge.name') }}</title>
    @vite(['resources/css/app.css'])
    @include('partials.theme')
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-6">
    <div class="w-full max-w-md text-center">
        <x-application-logo size="h-14 w-14" class="mx-auto" />
        <h1 class="mt-6 text-2xl font-semibold tracking-tight text-slate-900">We'll be right back</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ $message ?? 'We are performing scheduled maintenance. Please check back soon.' }}</p>
        <p class="mt-8 text-xs text-slate-400">{{ config('linkforge.name') }}</p>
    </div>
</body>
</html>
