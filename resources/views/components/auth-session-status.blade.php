@props(['status'])

@if ($status)
    <div role="status" aria-live="polite" {{ $attributes->merge(['class' => 'font-medium text-sm text-[#8B9A7A]']) }}>
        {{ $status }}
    </div>
@endif
