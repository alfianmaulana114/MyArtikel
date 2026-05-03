@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-2 focus:ring-[#AA5F3C] focus:ring-offset-2 focus:ring-offset-[color:var(--bg-primary)] disabled:opacity-60 disabled:cursor-not-allowed']) }}
>
