@props([
    // brand | amber  -> sobre fondo claro
    // on-dark        -> sobre foto con scrim o sobre --ink-surface
    'variant' => 'brand',
])
@php
    $variants = [
        'brand' => ['dot' => 'bg-action', 'text' => 'text-brand-text', 'bg' => 'bg-brand-50'],
        'amber' => ['dot' => 'bg-amber-text', 'text' => 'text-amber-text', 'bg' => 'bg-surface-2'],
        /*
         * Pase visual 2026-09-14. Sobre el scrim del hero (luminancia <= .055)
         * el blanco puro mide >= 4.5:1; el relleno translúcido solo separa la
         * pastilla de la foto, no aporta contraste, así que el texto va en
         * blanco puro y no en un gris claro.
         */
        'on-dark' => ['dot' => 'bg-brand-200', 'text' => 'text-white', 'bg' => 'bg-white/15 backdrop-blur-sm ring-1 ring-white/25'],
    ];
    $v = $variants[$variant] ?? $variants['brand'];
@endphp
<span {{ $attributes->class(['eyebrow inline-flex items-center gap-2 rounded-full px-3 py-1.5', $v['bg'], $v['text']]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $v['dot'] }}" aria-hidden="true"></span>
    {{ $slot }}
</span>
