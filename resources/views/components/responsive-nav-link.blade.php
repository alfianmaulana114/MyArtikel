@props(['active'])

@php
$classes = ($active ?? false)
            ? 'nav-link active block w-full ps-3 pe-4 py-2 border-l-4 border-amber-600 text-start text-base font-medium theme-bg-secondary theme-text-primary focus:outline-none transition duration-150 ease-in-out'
            : 'nav-link block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium theme-text-secondary hover:opacity-90 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
