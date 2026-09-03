@props([
    'variant' => 'green', // green | amber | orange | blue — los 4 acentos admitidos (ver tokens.css)
])
@php
    $variants = [
        'green' => 'bg-accent-green text-on-accent',
        'amber' => 'bg-accent-amber text-on-accent',
        'orange' => 'bg-accent-orange text-on-accent',
        'blue' => 'bg-accent-blue text-on-accent',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide', $variants[$variant] ?? $variants['green']]) }}>
    {{ $slot }}
</span>
