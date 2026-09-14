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
])
{{--
    Tarjeta-retrato de destino: foto a sangre con el rótulo encima. El
    degradado NO es decorativo — es lo único que hace que el texto blanco
    pase AA sobre una foto cualquiera — así que sale del token --scrim-card
    (.scrim-card), medido una vez en tokens.css, y no de una opacidad
    inventada acá.
--}}
<a
    href="{{ $href }}"
    {{ $attributes->class(['group relative block aspect-[4/5] overflow-hidden rounded-card shadow-e1 transition-shadow duration-300 hover:shadow-e3']) }}
>
    <span class="photo photo-zoom scrim-card absolute inset-0 block">
        <x-ui.picture :src="$image" :alt="$imageAlt" :sizes="$sizes" />
    </span>

    <span class="absolute inset-x-0 bottom-0 z-[2] flex items-end justify-between gap-3 p-5">
        <span class="min-w-0">
            <span class="block font-display text-h3 font-semibold text-white">{{ $name }}</span>
            @if($tourCount)
                <span class="eyebrow mt-1.5 block text-on-dark-2">{{ $tourCount }}</span>
            @elseif($tagline)
                <span class="mt-1 line-clamp-2 block text-sm text-on-dark-2">{{ $tagline }}</span>
            @endif
        </span>
        <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/30 backdrop-blur-sm transition-all duration-300 group-hover:bg-white group-hover:text-ink"
            aria-hidden="true"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
    </span>
</a>
