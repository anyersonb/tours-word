@props([
    'variant' => 'green', // green | amber | orange | blue — los 4 acentos admitidos (ver tokens.css)
])
@php
    $variants = [
        'green' => 'bg-accent-green text-on-accent',
        'amber' => 'bg-accent-amber text-on-accent',
        'orange' => 'bg-accent-orange text-on-accent',
        'blue' => 'bg-accent-blue text-on-accent',
        // Aditivo (mockups móvil, lote 16): pastilla clara para dato NEUTRO
        // sobre foto (p. ej. la duración real del tour) — no es un acento
        // de catálogo como los 4 de arriba, así que no deriva de --accent-*;
        // usa --surface/--ink directo, mismo contraste que cualquier chip
        // claro sobre foto (ver x-ui.destination-card, x-ui.mosaic-tile).
        'white' => 'bg-surface/95 text-ink shadow-e1',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide', $variants[$variant] ?? $variants['green']]) }}>
    {{ $slot }}
</span>
