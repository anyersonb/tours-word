@php
    use App\Support\PlaceholderImage;

    // Mismo respaldo que tours/show.blade.php (Defecto 1 del CRO): sin esto,
    // una experiencia sin fotos todavia deja la caja de x-ui.gallery vacia
    // (960x540 en blanco) en vez de mostrar el marcador de posicion
    // consistente que ya usa el resto del sitio.
    $galleryImages = collect($experience['gallery'])->map(fn ($image) => ['src' => $image['src'], 'alt' => $image['alt']])->all();
    if (empty($galleryImages)) {
        $galleryImages = [['src' => PlaceholderImage::svg(1200, 800, $experience['name'], '2c6fa8'), 'alt' => $experience['name']]];
    }

    $breadcrumbItems = [
        ['label' => __('site.experiences.index.breadcrumb.home'), 'href' => route('home')],
        ['label' => __('site.experiences.show.breadcrumb_index'), 'href' => route('experiences.index')],
        ['label' => $experience['name']],
    ];

    // Fix 3 (auditoria CRO/SEO): meta_title/meta_description dedicados,
    // traducibles, con respaldo honesto al nombre/descripcion cuando la
    // clienta todavia no los llena -- nunca una etiqueta <title> ni
    // "description" vacia (hoy pasaba con Trekking). Se pasa null (no '')
    // para que el "??" de x-layout SI dispare su propio respaldo de marca.
    //
    // Fix 2 (cierre lote SEO): "titleLiteral" es true solo cuando la clienta
    // SI escribio meta_title -- ese texto ya trae su propia marca ("... |
    // Pacha Viva") y x-layout no debe componerlo con "· Pacha Viva" encima
    // (duplicado real, medido en produccion). Cuando cae al respaldo
    // (nombre de la experiencia), x-layout SI compone, igual que antes.
    $metaTitle = filled($experience['meta_title'] ?? null) ? $experience['meta_title'] : $experience['name'];
    $titleLiteral = filled($experience['meta_title'] ?? null);
    $metaDescription = filled($experience['meta_description'] ?? null) ? $experience['meta_description'] : (filled($experience['description']) ? $experience['description'] : null);
@endphp
{{-- Fix 6 (lote SEO): "noindex" ya no es un booleano cableado -- combina el
     motivo MUESTRA (config('cms.catalog_demo_content'), independiente de
     este objetivo y que se apaga solo cuando la clienta cargue experiencias
     reales) con el fallback de contenido por locale (una experiencia sin
     traduccion al ingles no debe indexarse en "/en/"). "translatedLocales"
     deja que x-layout retire el hreflang hacia locales cuyo contenido para
     ESTA experiencia cae a fallback -- un hreflang que apunta a una URL
     noindex es una contradiccion. --}}
@php
    $noindex = config('cms.catalog_demo_content') || $contentFallbackLocale !== null;
    $translatedLocales = $experience->getTranslatedLocales('name');

    // El slug es una columna JSON traducible -- puede ser distinto por
    // locale. Sin esto, x-layout reutilizaria el slug de la request actual
    // para TODOS los alternates (ver el comentario de "hreflangUrls" en
    // components/layout.blade.php).
    $hreflangUrls = collect($translatedLocales)
        ->mapWithKeys(fn ($loc) => [$loc => route('experiences.show', [
            'locale' => \App\Support\Locale::toSegment($loc),
            'slug' => $experience->getTranslation('slug', $loc, false),
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

    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="$breadcrumbItems" />
        </div>

        <div class="mx-auto max-w-5xl px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <x-ui.gallery :images="$galleryImages" :label="__('site.ui.gallery.nav_label', ['title' => $experience['name']])" />

            {{-- Objetivo 2 (lote i18n): lang="es" honesto cuando esta
                 experiencia todavia no tiene su traduccion al locale de la
                 URL -- ver ResolvesBySlugByLocale. --}}
            @php($fallbackLangAttr = $contentFallbackLocale ? str_replace('_', '-', $contentFallbackLocale) : null)

            <h1 class="mt-6 font-display text-3xl font-semibold text-ink sm:text-4xl" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $experience['name'] }}</h1>
            <x-ui.content-fallback-notice :locale="$contentFallbackLocale" />
            <p class="mt-4 max-w-3xl text-base text-text-2 sm:text-lg" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $experience['description'] }}</p>
        </div>
    </section>

    {{-- ============ TOURS DE ESTA EXPERIENCIA ============ --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @if(!empty($relatedTours))
                <x-ui.section-title as="h2">
                    {{ __('site.experiences.show.related_tours_title', ['experience' => $experience['name']]) }}
                </x-ui.section-title>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($relatedTours as $tour)
                        <x-ui.tour-card
                            :image="$tour['images'][0]['src'] ?? PlaceholderImage::svg(480, 360, $tour['title'], '2c6fa8')"
                            :image-alt="$tour['images'][0]['alt'] ?? $tour['title']"
                            :title="$tour['title']"
                            :summary="$tour['summary']"
                            :duration="$tour['duration_label']"
                            :category="$tour['destination']['name'] ?? null"
                            :pen-cents="$tour['price_pen_cents']"
                            :usd-cents="$tour['price_usd_cents']"
                            :href="route('tours.show', $tour['slug'])"
                        />
                    @endforeach
                </div>
            @else
                <p class="text-sm text-text-2">{{ __('site.experiences.show.related_tours_empty') }}</p>
            @endif
        </div>
    </section>

    {{-- ============ CTA: VER TODOS LOS TOURS ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-8 text-center sm:px-6 lg:px-8">
            <x-ui.button variant="secondary" href="{{ route('tours.index') }}">
                {{ __('site.experiences.show.cta_all_tours') }}
            </x-ui.button>
        </div>
    </section>

</x-layout>
