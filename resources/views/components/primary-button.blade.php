<button {{ $attributes->merge(['type' => 'submit', 'class' => 'focus-ring inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700']) }}>
    {{ $slot }}
</button>
