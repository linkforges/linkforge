<x-app-layout title="Analytics">
    <x-slot:header>Analytics</x-slot:header>

    @if (($aiEnabled ?? false) && ($source ?? 'links') === 'links')
        @include('analytics.partials.ask')
    @endif

    @include('analytics.partials.report')
</x-app-layout>
