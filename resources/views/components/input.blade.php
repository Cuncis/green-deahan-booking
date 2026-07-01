@props(['label' => null, 'name', 'type' => 'text', 'placeholder' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">
            {{ $label }}
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:border-green focus:outline-none focus:ring-1 focus:ring-green transition-colors']) }}
    />
</div>
