@props(['active'])

@php
$classes = ($active ?? false)
            ? 'nav-link active inline-flex items-center px-1 pt-1 border-b-2 border-amber-600 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out'
            : 'nav-link inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
