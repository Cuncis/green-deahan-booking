@props(['paket'])

@php
$badgeClass = match ($paket) {
    'premium' => 'bg-plum text-white',
    'pro' => 'bg-gold text-white',
    default => 'bg-cream-deep text-ink-mid',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase $badgeClass"]) }}>
    {{ $paket }}
</span>
