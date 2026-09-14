@php
    use App\Support\PlaceholderImage;

    // Mismo respaldo que tours/show.blade.php (Defecto 1 del CRO): sin esto,
    // un destino/experiencia sin fotos todavia deja la caja de x-ui.gallery
    // vacia (960x540 en blanco) en vez de mostrar el marcador de posicion
    // consistente que ya usa el resto del sitio.
    $galleryImages = collect($destination['gallery'])->map(fn ($image) => ['src' => $image['src'], 'alt' => $image['alt']])->all();
    if (empty($galleryImages)) {
        $galleryImages = [['src' => PlaceholderImage::svg(1200, 800, $destination['name'], '2c6fa8'), 'alt' => $destination['name']]];
    }

    $breadcrumbItems = [
        ['label' => __('site.destinations.index.breadcrumb.home'), 'href' => route('home')],
        ['label' => __('site.destinations.show.breadcrumb_index'), 'href' => route('destinations.index')],
        ['label' => $destination['name']],
    ];

    // Fix 3 (auditoria CRO/SEO): meta_title/meta_description dedicados,
    // traducibles, con respaldo honesto al nombre/descripcion cuando la
    // clienta todavia no los llena -- nunca una etiqueta <title> ni
    // "description" vacia (hoy pasaba con Cusco). Se pasa null (no '') para
    // que el "??" de x-layout SI dispare su propio respaldo de marca.
    //
    // Fix 2 (cierre lote SEO): "titleLiteral" es true solo cuando la clienta
    // SI escribio meta_title -- ese texto ya trae su propia marca ("... |
    // Pacha Viva") y x-layout no debe componerlo con "· Pacha Viva" encima
    // (duplicado real, medido en produccion). Cuando cae al respaldo
    // (nombre del destino), x-layout SI compone, igual que antes.
    $metaTitle = filled($destination['meta_title'] ?? null) ? $destination['meta_title'] : $destination['name'];
    $titleLiteral = filled($destination['meta_title'] ?? null);
    $metaDescription = filled($destination['meta_description'] ?? null) ? $destination['meta_description'] : (filled($destination['description']) ? $destination['description'] : null);
@endphp
{{-- Fix 6 (lote SEO): "noindex" ya no es un booleano cableado -- combina el
     motivo MUESTRA (config('cms.catalog_demo_content'), independiente de
     este objetivo y que se apaga solo cuando la clienta cargue destinos
     reales) con el fallback de contenido por locale (un destino sin
     traduccion al ingles no debe indexarse en "/en/"). "translatedLocales"
     deja que x-layout retire el hreflang hacia locales cuyo contenido para
     ESTE destino cae a fallback -- un hreflang que apunta a una URL noindex
     es una contradiccion. -- --}}
@php
    $noindex = config('cms.catalog_demo_content') || $contentFallbackLocale !== null;
    $translatedLocales = $destination->getTranslatedLocales('name');

    // El slug es una columna JSON traducible -- puede ser distinto por
    // locale. Sin esto, x-layout reutilizaria el slug de la request actual
    // para TODOS los alternates (ver el comentario de "hreflangUrls" en
    // components/layout.blade.php).
    $hreflangUrls = collect($translatedLocales)
        ->mapWithKeys(fn ($loc) => [$loc => route('destinations.show', [
            'locale' => \App\Support\Locale::toSegment($loc),
            'slug' => $destination->getTranslation('slug', $loc, false),
        ])])
        ->all();
@endphp
<x-layout
    :title="$metaTitle"
    :title-literal="$titleLiteral"
    :description="$metaDescription"
    :noindex="$noindex"
    :translated-locales="$translatedLocales"
    :hreflang-urls="$hreflangUrls"
>

    {{-- ============ MIGAS DE PAN + GALERÍA + DESCRIPCIÓN ============ --}}
    <x-seo.breadcrumb-jsonld :items="$breadcrumbItems" />

    {{-- ============ CABECERA ============
         Antes la galeria iba primero y el h1 aparecia DEBAJO de una foto de
         600px: la pagina se leia sin encabezado, con el titulo colgando en
         medio. Ahora el titulo abre la ficha, igual que en los indices. --}}
    {{-- Objetivo 2 (lote i18n): lang="es" honesto cuando este destino todavia no tiene su traduccion
         al locale de la URL -- ver ResolvesBySlugByLocale. --}}
    @php($fallbackLangAttr = $contentFallbackLocale ? str_replace('_', '-', $contentFallbackLocale) : null)

    <section class="weave border-b border-sand-line bg-sand">
        <div class="shell section-tight">
            <x-ui.breadcrumbs :items="$breadcrumbItems" class="mb-6" />

            <h1 class="max-w-3xl font-display text-h1 font-semibold text-ink" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $destination['name'] }}</h1>
            <x-ui.content-fallback-notice :locale="$contentFallbackLocale" />
            <p class="mt-4 max-w-2xl text-lead text-text-2" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $destination['description'] }}</p>
        </div>
    </section>

    {{-- ============ GALERIA ============ --}}
    <section class="bg-surface">
        <div class="shell section">
            <div class="mx-auto max-w-5xl">
                <x-ui.gallery :images="$galleryImages" :label="__('site.ui.gallery.nav_label', ['title' => $destination['name']])" />
            </div>
        </div>
    </section>

    {{-- ============ TOURS RELACIONADOS ============ --}}
    <section class="weave bg-sand">
        <div class="shell section">
            <x-ui.section-title as="h2">
                {{ __('site.destinations.show.related_tours_title', ['destination' => $destination['name']]) }}
            </x-ui.section-title>

            @if(!empty($relatedTours))
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($relatedTours as $tour)
                        <x-ui.tour-card
                            :image="$tour['images'][0]['src'] ?? PlaceholderImage::svg(480, 360, $tour['title'], '2c6fa8')"
                            :image-alt="$tour['images'][0]['alt'] ?? $tour['title']"
                            :title="$tour['title']"
                            :summary="$tour['summary']"
                            :duration="$tour['duration_label']"
                            :category="$tour['experiences'][0]['name'] ?? null"
                            :location="$tour['destination']['name'] ?? null"
                            :pen-cents="$tour['price_pen_cents']"
                            :usd-cents="$tour['price_usd_cents']"
                            :href="route('tours.show', $tour['slug'])"
                            sizes="(min-width: 1024px) 24rem, (min-width: 640px) 45vw, 92vw"
                        />
                    @endforeach
                </div>
            @else
                {{-- Antes era un <p> suelto de 14px perdido en la seccion.
                     Mismo estado vacio que el resto del catalogo. --}}
                <x-ui.empty-state>{{ __('site.destinations.show.related_tours_empty') }}</x-ui.empty-state>
            @endif
        </div>
    </section>

    {{-- ============ CTA: VER TODOS LOS TOURS ============ --}}
    <section class="weave-dark bg-ink-surface">
        <div class="shell section-tight text-center">
            <a href="{{ route('tours.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-medium text-ink transition-colors hover:bg-brand-100">
                {{ __('site.destinations.show.cta_all_tours') }}
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    </section>
</x-layout>
