@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm theme-text-secondary']) }}>
    {{ $value ?? $slot }}
</label>
