@props([
    'variant' => 'green', // green | amber | gold | recommended — acentos admitidos (ver tokens.css)
    // Aditivas (lote 20, maqueta 3): 'lg' es la pastilla grande de la foto de
    // la tarjeta de tour (sin mayúsculas forzadas) y 'dot' le antepone el
    // punto de color. Defaults = el badge de siempre, sin cambio de píxel.
    'size' => 'sm', // sm | lg
    'dot' => false,
])
@php
    $variants = [
        'green' => 'bg-accent-green text-on-accent',
        'amber' => 'bg-accent-amber text-on-accent',
        'gold' => 'bg-gold text-espresso',
        'recommended' => 'bg-espresso text-gold ring-1 ring-gold/40',
        // Aditivo (mockups móvil, lote 16): pastilla clara para dato NEUTRO
        // sobre foto (p. ej. la duración real del tour) — no es un acento
        // de catálogo como los 4 de arriba, así que no deriva de --accent-*;
        // usa --surface/--ink directo, mismo contraste que cualquier chip
        // claro sobre foto (ver x-ui.destination-card, x-ui.mosaic-tile).
        'white' => 'bg-surface/95 text-ink shadow-e1',
    ];
    $sizes = [
        'sm' => 'gap-1 px-2.5 py-1 text-[11px] uppercase tracking-wide',
        'lg' => 'gap-2 px-3.5 py-1.5 text-[13px] leading-tight',
    ];
    $dotClass = match ($variant) {
        'white' => 'bg-accent-green',
        'gold' => 'bg-espresso/70',
        default => 'bg-on-accent/70',
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full font-semibold', $sizes[$size] ?? $sizes['sm'], $variants[$variant] ?? $variants['green']]) }}>
    @if($dot)
        <span class="h-2 w-2 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
