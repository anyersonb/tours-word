@props([
    'image',
    'imageAlt' => '',
    'title',
    'subtitle' => null,
    'href' => '#',
    'position' => null, // object-position — elegido foto por foto, nunca "center" a ciegas
    'sizes' => '50vw',
    // El nivel lo decide quien coloca el tile: en el mosaico de home no hay
    // H2 visible arriba (ver comentario en home.blade.php), así que cada
    // tile es un h3 suelto — mismo criterio que x-ui.destination-card.
    'headingLevel' => 3,
    // Prop aditiva (lote 1, sección "Actividades"): pastilla de ícono
    // arriba a la izquierda, mismo lenguaje que x-ui.experience-card pero
    // sin shadow-e1 (ring + blur en vez de sombra). El mosaico de home no
    // la usa (queda null), así que su salida no cambia.
    'icon' => null,
])
@php
    $hl = max(2, min(4, (int) $headingLevel));
@endphp
{{--
    Tile del mosaico Roavio (port fiel, 2026-09-20): foto a sangre + rótulo
    abajo a la izquierda sobre scrim, radio de 10px (--r-tile, no el
    --r-lg/xl que usan las tarjetas del resto del sitio — ver tokens.css).
    Reutiliza el MISMO scrim medido (--scrim-card) que destination-card, no
    una opacidad nueva inventada para esta pantalla.
--}}
{{--
    Sin h-full fijo a propósito: en el grid parejo de móvil/tablet el
    aspect-[3/4] que pasa el caller gobierna el alto (no hay fila fija que
    "llenar"); en la retícula asimétrica de escritorio (lg:aspect-auto +
    lg:row-span-2 en las columnas altas) el "align-items: stretch" por
    defecto de CSS Grid ya estira el item a la altura de su fila/filas sin
    necesidad de forzarlo con una utilidad.
--}}
<a
    href="{{ $href }}"
    {{ $attributes->class(['group relative block w-full overflow-hidden rounded-tile']) }}
>
    <span class="photo photo-zoom scrim-card absolute inset-0 block">
        <x-ui.picture :src="$image" :alt="$imageAlt" :sizes="$sizes" :position="$position" />
    </span>

    @if($icon)
        <span class="absolute left-4 top-4 z-[2] flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/30 backdrop-blur-sm sm:left-5 sm:top-5" aria-hidden="true">
            {!! $icon !!}
        </span>
    @endif

    <div class="absolute inset-x-0 bottom-0 z-[2] p-4 sm:p-5">
        <h{{ $hl }} class="font-display text-base font-semibold leading-tight text-white sm:text-lg">{{ $title }}</h{{ $hl }}>
        @if($subtitle)
            <span class="mt-1 block text-xs text-on-dark-2">{{ $subtitle }}</span>
        @endif
    </div>
</a>
