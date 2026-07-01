@props(['type' => 'info'])

@php
$variants = [
    'confirmed' => 'bg-green-pale text-green',
    'pending' => 'bg-amber-pale text-amber',
    'cancelled' => 'bg-danger-pale text-danger',
    'info' => 'bg-cream-deep text-ink-mid',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold '.($variants[$type] ?? $variants['info'])]) }}>
    {{ $slot }}
</span>
