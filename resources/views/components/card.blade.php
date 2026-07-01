@props([])

<div {{ $attributes->merge(['class' => 'bg-white border border-cream-deep rounded-card p-5 md:p-6']) }}>
    {{ $slot }}
</div>
