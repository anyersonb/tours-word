@php
    use App\Support\PlaceholderImage;

    // Mismos iconos y mismas claves (slug) que ya usa home.blade.php para no
    // introducir un segundo mapa de iconos de experiencia.
    $experienceIcons = [
        'trekking' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="m3 20 6-10 4 6 2-3 6 7H3Z"/></svg>',
        'gastronomia' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M6 3v7a2 2 0 0 0 2 2 2 2 0 0 0 2-2V3M8 12v9M17 3c-1.5 0-3 1.5-3 4s1.5 4 3 4v9"/></svg>',
        'cultura' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M4 8c0-3 3.5-5 8-5s8 2 8 5-3.5 9-8 9-8-6-8-9Z"/><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><path d="M9 13c1 1 5 1 6 0"/></svg>',
    ];
    $defaultExperienceIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-2 6-6 2 2-6 6-2Z"/></svg>';

    /**
     * Cabecera con foto (lote 2, port Roavio) — mismo pendiente de la
     * clienta que destinations/index ("cabecera con foto en Destinos y
     * Experiencias"). Igual criterio: foto de GALERÍA real (ya en BD,
     * $experiences trae ->with('gallery') desde el controller), nunca la
     * portada (esa ya se ve en cada tile de la rejilla de abajo) ni una
     * foto de site/ ya usada en Home/tours. Sin foto de galería, cae a
     * portada; sin ninguna de las dos, $headerImage queda null y
     * x-ui.page-header cae entera a la banda cálida sin foto.
     */
    $headerGalleryPhoto = $experiences->first(fn ($e) => $e->gallery->isNotEmpty())?->gallery->first();
    $headerExperienceFallback = $experiences->first();
    $headerImage = $headerGalleryPhoto?->src ?? $headerExperienceFallback?->coverImageUrl();
    $headerImageAlt = $headerGalleryPhoto?->alt
        ?? $headerExperienceFallback?->cover_image_alt
        ?? $headerExperienceFallback?->name;
@endphp
{{-- El noindex ya no se cablea aca: mientras
     config("cms.catalog_demo_content") este en true, x-layout pone
     "noindex, nofollow" en TODO el sitio publico. Cableandolo en esta
     vista, bajar la bandera dejaba los tres indices fuera del indice sin
     que nadie lo notara. --}}
<x-layout
    :title="__('site.experiences.index.meta.title')"
    :description="__('site.experiences.index.meta.description')"
>

    {{-- ============ CABECERA ============
         Puerto Roavio (lote 2): foto real de galería + .shell-boxed (1392)
         — distinto de los otros dos catálogos (tours=.shell/1280,
         destinos=.shell-bleed/1432), y del ancho de la rejilla de abajo
         (regla del encargo). No cinematic (esa es la firma de tours/index):
         banda estándar, más baja que la cabecera de tours pero más alta
         que la de destinos (--scrim-band, mismo tratamiento). --}}
    <x-ui.page-header
        :breadcrumbs="[
            ['label' => __('site.experiences.index.breadcrumb.home'), 'href' => route('home')],
            ['label' => __('site.experiences.index.breadcrumb.current')],
        ]"
        :title="__('site.experiences.index.hero.title')"
        :lead="__('site.experiences.index.hero.subtitle')"
        :image="$headerImage"
        :image-alt="$headerImageAlt"
        container-class="shell-boxed"
        {{-- object-position mirado en el navegador: el default (45%) dejaba
             sobre todo cielo nublado plano en la mitad superior de Vinicunca
             (Montaña de 7 Colores); 55% baja el encuadre hasta la cresta de
             franjas de color y el arranque del sendero con caminantes, que
             es el contenido con más lectura de la foto a esta relación de
             aspecto (~5.5:1). --}}
        position="center 55%"
    />

    {{-- ============ REJILLA ============
         Puerto Roavio (lote 2): en vez de x-ui.experience-card (tarjeta con
         borde/sombra, que sigue viva sin tocar en /_styleguide), esta
         rejilla reutiliza x-ui.mosaic-tile — EL MISMO componente que ya usa
         "Actividades" en la Home para esta misma colección ($experiences,
         mismo dato, cero query nueva) — en vez de inventar una cuarta
         tarjeta. Tile ancho a sangre (aspect-video, no aspect-[3/4] como el
         mosaico del hero) dentro de .shell-bleed (1432): la rejilla MÁS
         ancha de los tres catálogos, justo lo opuesto de la cabecera boxed
         de arriba — el contraste boxed→sangre es la firma Roavio (ver
         lote 0, mosaico bajo el hero). --}}
    <section class="bg-surface">
        <div class="shell-bleed section">
            @if(empty($experiences))
                <x-ui.empty-state>{{ __('site.experiences.index.empty') }}</x-ui.empty-state>
            @else
                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),1fr))]">
                    @foreach($experiences as $i => $experience)
                        <x-ui.mosaic-tile
                            class="aspect-video"
                            :image="$experience['gallery'][0]['src'] ?? PlaceholderImage::svg(480, 360, $experience['name'], '2c6fa8')"
                            :image-alt="$experience['cover_image_alt'] ?? $experience['name']"
                            :title="$experience['name']"
                            :subtitle="\Illuminate\Support\Str::limit($experience['description'], 60)"
                            :icon="$experienceIcons[$experience['slug']] ?? $defaultExperienceIcon"
                            :href="route('experiences.show', $experience['slug'])"
                            sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 90vw"
                            {{-- Nivel 2, igual que en los otros dos índices:
                                 la tarjeta cuelga del H1 de la pantalla. --}}
                            heading-level="2"
                            data-reveal
                            style="--reveal-delay:{{ min($i, 5) * 80 }}ms"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

</x-layout>
