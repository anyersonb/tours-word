@php
    use App\Support\Html\RichTextSanitizer;
    use App\Support\PlaceholderImage;

    // Objetivo (lote i18n, 2026-09-14): Tour::meta_title/meta_description ya
    // son editables en Filament y están sembrados, pero esta ficha usaba
    // title/summary a secas -- lo que la clienta escribe en esos campos
    // nunca llegaba al <title>/<meta description>. Con respaldo: un
    // meta_title/meta_description vacío ($tour['meta_title'] === '' cuando
    // el tour no tiene ese campo traducido, o nunca se completó) cae al
    // título/resumen, nunca un <title> vacío.
    //
    // Fix 2 (cierre lote SEO, 2026-09-14): "titleLiteral" es true solo
    // cuando la clienta SI escribio meta_title -- ese texto ya trae su
    // propia marca ("... | Pacha Viva") y x-layout no debe componerlo con
    // "· Pacha Viva" encima (duplicado real, medido en produccion:
    // "[MUESTRA] Camino Inca 4 días | Pacha Viva · Pacha Viva"). Cuando cae
    // al respaldo (titulo del tour), x-layout SI compone, igual que antes.
    $metaTitle = filled($tour['meta_title'] ?? null) ? $tour['meta_title'] : $tour['title'];
    $titleLiteral = filled($tour['meta_title'] ?? null);
    $metaDescription = filled($tour['meta_description'] ?? null) ? $tour['meta_description'] : $tour['summary'];

    $galleryImages = collect($tour['images'])->map(fn ($image) => ['src' => $image['src'], 'alt' => $image['alt']])->all();
    if (empty($galleryImages)) {
        $galleryImages = [['src' => PlaceholderImage::svg(1200, 800, $tour['title'], '2c6fa8'), 'alt' => $tour['title']]];
    }

    $breadcrumbItems = [
        ['label' => __('site.tours.index.breadcrumb.home'), 'href' => route('home')],
        ['label' => __('site.tours.show.breadcrumb_index'), 'href' => route('tours.index')],
        ['label' => $tour['title']],
    ];

    // Íconos: mismo lenguaje visual (stroke-width 2, viewBox 24x24) que el
    // resto del sitio (home/nosotros/contacto), para no introducir un
    // segundo estilo.
    $iconCheck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0 text-action" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $iconX = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0 text-text-muted" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>';
    $iconGauge = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="m12 14 3-5"/><circle cx="12" cy="14" r="1"/><path d="M4 15a8 8 0 1 1 16 0"/></svg>';
    $iconPin = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
@endphp
{{-- Defecto 1 (cierre lote SEO, 2026-09-14): la ficha de tour era la unica
     de las 3 fichas de catalogo sin BreadcrumbList/hreflang/noindex por
     fallback -- se le aplica exactamente el mismo patron ya validado en
     destinations/show.blade.php y experiences/show.blade.php. "noindex"
     combina el motivo MUESTRA (config('cms.catalog_demo_content')) con el
     fallback de contenido por locale (un tour sin traduccion al ingles no
     debe indexarse en "/en/"). "hreflangUrls" evita el bug de reusar el
     slug del locale actual para todos los alternates (slug es columna
     traducible) -- ver el comentario de "hreflangUrls" en
     components/layout.blade.php. --}}
@php
    $noindex = config('cms.catalog_demo_content') || $contentFallbackLocale !== null;
    $translatedLocales = $tour->getTranslatedLocales('title');

    $hreflangUrls = collect($translatedLocales)
        ->mapWithKeys(fn ($loc) => [$loc => route('tours.show', [
            'locale' => \App\Support\Locale::toSegment($loc),
            'slug' => $tour->getTranslation('slug', $loc, false),
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

    {{-- ============ MIGAS DE PAN + GALERÍA + PRECIO/CTA ============ --}}
    <x-seo.breadcrumb-jsonld :items="$breadcrumbItems" />

    <section class="border-b border-sand-line bg-sand">
        <div class="shell py-4">
            <x-ui.breadcrumbs :items="$breadcrumbItems" />
        </div>
    </section>

    <section class="bg-surface">
        <div class="shell section">
            <div class="grid gap-10 lg:grid-cols-[3fr_2fr] lg:items-start lg:gap-14">
            {{-- min-w-0: sin esto el item de rejilla toma el min-content de la
                 tira de miniaturas (5 x 80px + huecos = 440px) y la ficha
                 desbordaba 96px a 360. Medido con getBoundingClientRect. --}}
            <div class="min-w-0">
                <x-ui.gallery :images="$galleryImages" :label="__('site.ui.gallery.nav_label', ['title' => $tour['title']])" />

                {{-- Objetivo 2 (lote i18n): lang="es" honesto en cada bloque
                     de contenido de catalogo cuando este tour todavia no
                     tiene su traduccion al locale de la URL -- nunca se
                     finge que este texto esta en el idioma de la pagina.
                     Ver ResolvesBySlugByLocale y el aviso de abajo. --}}
                @php($fallbackLangAttr = $contentFallbackLocale ? str_replace('_', '-', $contentFallbackLocale) : null)

                @if($tour['destination'])
                    <a href="{{ route('destinations.show', $tour['destination']['slug']) }}" class="mt-8 inline-flex items-center gap-1.5 text-sm font-medium text-brand-text transition-colors hover:text-action hover:underline">
                        {!! $iconPin !!}{{ $tour['destination']['name'] }}
                    </a>
                @endif

                <h1 class="mt-3 font-display text-h1 font-semibold text-ink" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $tour['title'] }}</h1>

                <x-ui.content-fallback-notice :locale="$contentFallbackLocale" />

                <p class="mt-4 max-w-2xl text-lead text-text-2" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $tour['summary'] }}</p>

                {{-- Defecto 1 (auditoria cliente, 2026-09-14): "description"
                     se edita con Filament\Forms\Components\RichEditor (ver
                     TourForm) -- es HTML de la clienta, no texto plano.
                     Se escapaba y mostraba los tags en pantalla;
                     App\Support\Html\RichTextSanitizer lo limpia (whitelist
                     exacta de la toolbar del editor) antes de imprimirlo. --}}
                <div class="prose-pv mt-6 max-w-2xl text-base leading-relaxed text-text-2" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>
                    {!! RichTextSanitizer::sanitize($tour['description']) !!}
                </div>
            </div>

            {{-- Panel de reserva. Antes era una caja casi vacia (precio +
                 boton) que dejaba una columna entera en blanco, mientras
                 duracion, dificultad y punto de encuentro estaban repartidos
                 por la columna izquierda. Ahora viven aca, que es donde se
                 decide la compra. --}}
            <aside class="min-w-0 lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-panel border border-line bg-surface shadow-e3">
                    <div class="border-b border-line-soft bg-brand-50 px-6 py-5">
                        <x-ui.money
                            :pen-cents="$tour['price_pen_cents']"
                            :usd-cents="$tour['price_usd_cents']"
                            :prefix="__('site.tours.show.price_prefix')"
                            class="font-display text-h2 font-semibold text-ink"
                        />
                    </div>

                    <div class="p-6">
                        <x-ui.button href="{{ route('contact') }}" class="w-full justify-center px-6 py-3 text-base">
                            {{ __('site.tours.show.cta_reserve') }}
                        </x-ui.button>

                        @if($tour['duration_label'] || $tour['difficulty'] || $tour['meeting_point'])
                            <dl class="mt-6 flex flex-col gap-4 border-t border-line-soft pt-5 text-sm">
                                @if($tour['duration_label'])
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 text-action">{!! $iconClock !!}</span>
                                        <div>
                                            <dt class="font-medium text-ink">{{ __('site.tours.show.duration_label') }}</dt>
                                            <dd class="text-text-2">{{ $tour['duration_label'] }}</dd>
                                        </div>
                                    </div>
                                @endif
                                @if($tour['difficulty'])
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 text-action">{!! $iconGauge !!}</span>
                                        <div>
                                            {{-- El valor es App\Enums\TourDifficulty (Tour::$casts),
                                                 nunca un string: por eso ->value. Ver el comentario
                                                 largo del lote en el historial de este archivo. --}}
                                            <dt class="font-medium text-ink">{{ __('site.tours.show.difficulty_label') }}</dt>
                                            <dd class="text-text-2">{{ __('tours.difficulty.'.$tour['difficulty']->value) }}</dd>
                                        </div>
                                    </div>
                                @endif
                                @if($tour['meeting_point'])
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 text-action">{!! $iconPin !!}</span>
                                        <div>
                                            <dt class="font-medium text-ink">{{ __('site.tours.show.meeting_point_title') }}</dt>
                                            <dd class="text-text-2">{{ $tour['meeting_point'] }}</dd>
                                        </div>
                                    </div>
                                @endif
                            </dl>
                        @endif
                    </div>
                </div>
            </aside>
            </div>
        </div>
    </section>

    {{--
        Defecto 2 (auditoria cliente, 2026-09-14): itinerary/inclusions/
        exclusions son columnas JSON traducibles -- a diferencia de title/
        summary (string), un valor "[]" para este locale cuenta como
        "traducido" para Spatie a menos que Tour::filterTranslations() lo
        trate como vacio (ver ese metodo). Con el fix, isContentFallbackFor()
        ya distingue esto POR CAMPO: title/summary pueden estar en ingles
        real mientras itinerary/inclusions/exclusions caen al español -- no
        se puede reusar $contentFallbackLocale (calculado solo sobre
        "title") para estas tres secciones, cada una necesita su propio
        aviso y su propio lang="" honesto.
    --}}
    @php($itineraryLangAttr = $itineraryFallbackLocale ? str_replace('_', '-', $itineraryFallbackLocale) : null)
    @php($inclusionsLangAttr = $inclusionsFallbackLocale ? str_replace('_', '-', $inclusionsFallbackLocale) : null)
    @php($exclusionsLangAttr = $exclusionsFallbackLocale ? str_replace('_', '-', $exclusionsFallbackLocale) : null)

    {{-- ============ ITINERARIO ============ --}}
    @if(!empty($tour['itinerary']))
        <section class="weave bg-sand">
            <div class="shell section">
              <div class="mx-auto max-w-3xl">
                <x-ui.section-title as="h2">{{ __('site.tours.show.itinerary_title') }}</x-ui.section-title>

                <x-ui.content-fallback-notice :locale="$itineraryFallbackLocale" />

                <div @if($itineraryLangAttr) lang="{{ $itineraryLangAttr }}" @endif>
                    @foreach($tour['itinerary'] as $day)
                        {{-- Mismo criterio que "description" arriba: HTML de
                             la clienta (RichEditor), saneado antes de
                             imprimirse. --}}
                        <x-ui.faq-item :question="$day['title']">{!! RichTextSanitizer::sanitize($day['description']) !!}</x-ui.faq-item>
                    @endforeach
                </div>
              </div>
            </div>
        </section>
    @endif

    {{-- ============ INCLUYE / NO INCLUYE ============ --}}
    @if(!empty($tour['inclusions']) || !empty($tour['exclusions']))
        <section class="bg-surface">
            <div class="shell section">
              <div class="mx-auto grid max-w-4xl gap-6 sm:grid-cols-2">
                @if(!empty($tour['inclusions']))
                    <div class="rounded-panel border border-line bg-surface p-6 shadow-e1" @if($inclusionsLangAttr) lang="{{ $inclusionsLangAttr }}" @endif>
                        <h2 class="font-display text-h3 font-semibold text-ink">{{ __('site.tours.show.inclusions_title') }}</h2>
                        <x-ui.content-fallback-notice :locale="$inclusionsFallbackLocale" />
                        <ul class="mt-4 flex flex-col gap-3">
                            @foreach($tour['inclusions'] as $item)
                                <li class="flex items-start gap-2 text-sm text-text-2">{!! $iconCheck !!}<span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($tour['exclusions']))
                    <div class="rounded-panel border border-line bg-surface p-6 shadow-e1" @if($exclusionsLangAttr) lang="{{ $exclusionsLangAttr }}" @endif>
                        <h2 class="font-display text-h3 font-semibold text-ink">{{ __('site.tours.show.exclusions_title') }}</h2>
                        <x-ui.content-fallback-notice :locale="$exclusionsFallbackLocale" />
                        <ul class="mt-4 flex flex-col gap-3">
                            @foreach($tour['exclusions'] as $item)
                                <li class="flex items-start gap-2 text-sm text-text-2">{!! $iconX !!}<span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
              </div>
            </div>
        </section>
    @endif

    {{-- ============ CTA: MÁS TOURS EN ESTE DESTINO ============ --}}
    @if($tour['destination'])
        <section class="weave-dark bg-ink-surface">
            <div class="shell section-tight flex flex-col items-start gap-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="max-w-xl font-display text-h3 font-semibold text-white">
                    {{ __('site.tours.show.cta_banner_title', ['destination' => $tour['destination']['name']]) }}
                </p>
                <a href="{{ route('tours.index', ['destino' => $tour['destination']['slug']]) }}" class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-medium text-ink transition-colors hover:bg-brand-100 sm:w-auto">
                    {{ __('site.tours.show.cta_banner_button') }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        </section>
    @endif

</x-layout>
