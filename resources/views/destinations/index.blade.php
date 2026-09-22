@php
    use App\Support\PlaceholderImage;

    /**
     * Cabecera con foto (lote 2, port Roavio) — pendiente explícito de la
     * clienta: "cabecera con foto en Destinos y Experiencias". Antes esta
     * pantalla no llevaba foto porque las 3 del banco de site/ ya las usan
     * Home y tours/index; en vez de repetir una de esas, se toma una foto
     * de la GALERÍA real de un destino (ya en BD, $destinations trae
     * ->with('gallery') desde el controller) que hoy no aparece en ningún
     * otro lado del sitio — cero contenido nuevo, cero foto de banco.
     *
     * Nunca se hardcodea la ruta del archivo (regla del modelo: "Views/
     * Resources must use this accessor — never build the URL by hand"):
     * se recorre la colección ya cargada y se usa el accessor ->src / el
     * atributo ->alt (traducible, Spatie HasTranslations resuelve el
     * idioma actual solo). Si el catálogo llegara sin fotos de galería
     * todavía, cae a portada; sin portada, $headerImage queda null y
     * x-ui.page-header entero cae a su variante sin foto (banda cálida) —
     * nunca una imagen de relleno inventada.
     */
    $headerGalleryPhoto = $destinations
        ->first(fn ($d) => $d->gallery->count() >= 3)
        ?->gallery->get(2)
        ?? $destinations->first(fn ($d) => $d->gallery->isNotEmpty())?->gallery->first();
    $headerDestinationFallback = $destinations->first();
    $headerImage = $headerGalleryPhoto?->src ?? $headerDestinationFallback?->coverImageUrl();
    $headerImageAlt = $headerGalleryPhoto?->alt
        ?? $headerDestinationFallback?->cover_image_alt
        ?? $headerDestinationFallback?->name;
@endphp
{{-- El noindex ya no se cablea aca: mientras
     config("cms.catalog_demo_content") este en true, x-layout pone
     "noindex, nofollow" en TODO el sitio publico. Cableandolo en esta
     vista, bajar la bandera dejaba los tres indices fuera del indice sin
     que nadie lo notara. --}}
<x-layout
    :title="__('site.destinations.index.meta.title')"
    :description="__('site.destinations.index.meta.description')"
>

    {{-- ============ CABECERA ============
         Puerto Roavio (lote 2): foto real de galería + .shell-bleed (1432,
         a sangre) — a propósito el MÁS ancho de los tres, para que esta
         cabecera se sienta la más "panorámica" del catálogo (contraste
         fuerte contra la de tours/index, que es .shell/1280). No cinematic
         (esa variante es la firma de tours/index): banda estándar más baja
         (--scrim-band), así las 3 cabeceras no quedan con la misma altura. --}}
    <x-ui.page-header
        :breadcrumbs="[
            ['label' => __('site.destinations.index.breadcrumb.home'), 'href' => route('home')],
            ['label' => __('site.destinations.index.breadcrumb.current')],
        ]"
        :title="__('site.destinations.index.hero.title')"
        :lead="__('site.destinations.index.hero.subtitle')"
        :image="$headerImage"
        :image-alt="$headerImageAlt"
        container-class="shell-bleed"
        {{-- object-position mirado en el navegador (no "center" a ciegas):
             a la relación de aspecto de esta banda (~5.5:1) el default del
             componente (45%) dejaba la mitad superior de la foto -- cielo
             nublado y las torres de la catedral en silueta oscura -- y
             recortaba la plaza iluminada con gente que es el contenido con
             más lectura de la foto. 62% baja el encuadre hasta la fachada
             iluminada + el empedrado con reflejos + la gente caminando. --}}
        position="center 62%"
    />

    {{-- ============ REJILLA ============
         .shell-narrow (1290) — distinto del ancho de la cabecera de arriba
         (regla del encargo) y de la rejilla de tours/index (.shell-boxed,
         1392): el tercer ancho Roavio, que hasta el lote 1 solo estrenaba
         Testimonios en la Home. Tarjetas variant="flat" (radio 10px, CERO
         box-shadow) — prop aditiva nueva de x-ui.destination-card; la Home
         ("Destinos imperdibles") y /_styleguide siguen con el variant="card"
         de siempre, sin tocar. --}}
    <section class="bg-surface">
        <div class="shell-narrow section">
            @if(empty($destinations))
                <x-ui.empty-state>{{ __('site.destinations.index.empty') }}</x-ui.empty-state>
            @else
                {{-- auto-fit con 1fr: las tarjetas llenan la fila. El tope de
                     280px anterior las dejaba encogidas y centradas, con un
                     hueco de ~170px a la izquierda del contenedor. --}}
                <div class="grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,14rem),1fr))]">
                    @foreach($destinations as $i => $destination)
                        <x-ui.destination-card
                            variant="flat"
                            :image="$destination['gallery'][0]['src'] ?? PlaceholderImage::svg(480, 600, $destination['name'], '2c6fa8')"
                            :image-alt="$destination['cover_image_alt'] ?? $destination['name']"
                            :name="$destination['name']"
                            :tagline="$destination['description']"
                            :href="route('destinations.show', $destination['slug'])"
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
