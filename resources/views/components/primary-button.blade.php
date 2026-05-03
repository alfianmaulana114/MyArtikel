<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-sm text-white tracking-wide bg-gradient-to-br from-[#A35A39] to-[#6F3418] shadow-sm hover:shadow-md hover:shadow-[#AA5F3C]/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#AA5F3C] focus-visible:ring-offset-2 focus-visible:ring-offset-[color:var(--bg-primary)] active:translate-y-px active:opacity-95 disabled:opacity-50 disabled:pointer-events-none transition']) }}>
    {{ $slot }}
</button>
