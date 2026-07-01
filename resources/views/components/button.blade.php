@props(['variant' => 'primary'])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 transition-colors';

$variants = [
    'primary' => 'bg-green text-white hover:bg-green-mid',
    'secondary' => 'border-2 border-sand text-ink-mid hover:border-brown-light',
    'gold' => 'bg-gold text-white',
    'danger' => 'bg-danger/10 text-danger border border-danger/20',
];
@endphp

<button {{ $attributes->merge(['class' => $base.' '.($variants[$variant] ?? $variants['primary'])]) }}>
    {{ $slot }}
</button>
