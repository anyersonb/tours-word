@props([
    'image',
    'imageAlt' => '',
    'name',
    'tagline' => null,
    'href' => '#',
    'tourCount' => null,
    /*
     * OJO: aca "sizes" NO es el ancho de la tarjeta, es el ancho de ORIGEN
     * que hace falta. La caja es retrato 4:5 y las fotos del catalogo son
     * apaisadas (3:2, y destino-valle-sagrado.jpg es 1200x429). Con
     * object-fit:cover, el recorte escala por ALTO, asi que el origen tiene
     * que ser ~1.9x el ancho de la caja. Declarando el ancho de la caja a
     * secas el navegador se bajaba la variante de 400px y la escalaba a
     * 1119px: la tarjeta de Valle Sagrado salia borrosa (visto en captura a
     * 1440, no deducido). El factor 1.9 = (5/4 de la caja) x (3/2 del origen).
     */
    'sizes' => '(min-width: 1024px) 36rem, (min-width: 640px) 84vw, 168vw',
    // Mismo contrato que x-ui.tour-card: el nivel lo decide la pantalla que
    // coloca la tarjeta (2 en un índice, 3 dentro de una sección con su H2).
    'headingLevel' => 3,
    // Prop aditiva (lote 2, port Roavio): 'card' es el look de siempre
    // (radius-card, shadow-e1/e3) que sigue usando la Home ("Destinos
    // imperdibles") y /_styleguide — NO SE TOCA. 'flat' es el tratamiento
    // Roavio (radius-tile, CERO box-shadow en cualquier sitio) que estrena
    // destinations/index.blade.php — mismo patrón que x-ui.tour-card.
    'variant' => 'card',
])
@php
    $hl = max(2, min(4, (int) $headingLevel));
    $surfaceClasses = $variant === 'flat'
        ? 'rounded-tile transition-transform duration-300 hover:-translate-y-1 focus-visible:-translate-y-1'
        : 'rounded-card shadow-e1 transition-shadow duration-300 hover:shadow-e3';
@endphp
{{--
    Tarjeta-retrato de destino: foto a sangre con el rótulo encima. El
    degradado NO es decorativo — es lo único que hace que el texto blanco
    pase AA sobre una foto cualquiera — así que sale del token --scrim-card
    (.scrim-card), medido una vez en tokens.css, y no de una opacidad
    inventada acá.
--}}
<a
    href="{{ $href }}"
    {{ $attributes->class(['group relative block aspect-[4/5] overflow-hidden', $surfaceClasses]) }}
>
    <span class="photo photo-zoom scrim-card absolute inset-0 block">
        <x-ui.picture :src="$image" :alt="$imageAlt" :sizes="$sizes" />
    </span>

    {{-- Contenedores en <div> y no en <span>: el rótulo es un encabezado de
         verdad (h2 en el índice, h3 dentro de una sección), y un <span> solo
         admite contenido de texto. El <a> envolvente sí lo admite: su modelo
         de contenido es transparente, así que hereda el del <div> de la
         rejilla. Se mantiene el <a> como envoltorio — la tarjeta entera es el
         objetivo táctil — en vez de un enlace estirado como el de tour-card,
         porque acá el rótulo vive dentro de una caja "absolute" y el ::before
         se recortaría a esa franja en vez de cubrir la tarjeta. --}}
    <div class="absolute inset-x-0 bottom-0 z-[2] flex items-end justify-between gap-3 p-5">
        <div class="min-w-0">
            <h{{ $hl }} class="font-display text-h3 font-semibold text-white">{{ $name }}</h{{ $hl }}>
            @if($tourCount)
                <span class="eyebrow mt-1.5 block text-on-dark-2">{{ $tourCount }}</span>
            @elseif($tagline)
                {{-- Sin "block": la utilidad de display de Tailwind se emite
                     DESPUES de line-clamp en la hoja, asi que ganaba y dejaba el
                     -webkit-line-clamp inerte. Con los textos cortos del catalogo
                     de muestra no se notaba; con las descripciones reales el
                     resumen se iba a 9 lineas y tapaba la foto. line-clamp-2 ya
                     pone el display que necesita. --}}
                <span class="mt-1 line-clamp-2 text-sm text-on-dark-2">{{ $tagline }}</span>
            @endif
        </div>
        <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/30 backdrop-blur-sm transition-all duration-300 group-hover:bg-white group-hover:text-ink"
            aria-hidden="true"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
    </div>
</a>
