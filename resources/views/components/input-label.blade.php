@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1']) }}>
    {{ $value ?? $slot }}
</label>
