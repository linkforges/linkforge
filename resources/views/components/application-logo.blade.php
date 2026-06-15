@props(['size' => 'h-9 w-9'])

@php $logo = config('linkforge.logo'); @endphp
@if ($logo)
    <img src="{{ $logo }}" alt="{{ config('linkforge.name') }}" {{ $attributes->merge(['class' => "shrink-0 object-contain $size !w-auto max-w-[150px]"]) }}>
@else
    <span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-xl text-white shadow-sm $size"]) }}
          style="background-image:linear-gradient(135deg,var(--color-brand-500),var(--color-brand-700))">
        <svg class="h-1/2 w-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757" />
            <path d="M10.81 15.312a4.5 4.5 0 0 1-1.242-7.244l4.5-4.5a4.5 4.5 0 0 1 6.364 6.364l-1.757 1.757" />
        </svg>
    </span>
@endif
