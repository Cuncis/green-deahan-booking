<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 bg-green text-white hover:bg-green-mid transition-colors']) }}>
    {{ $slot }}
</button>
