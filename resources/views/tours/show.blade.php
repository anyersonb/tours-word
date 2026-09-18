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

    // Pase cinematográfico (pasada B, 2026-09-18): hero de ficha con la
    // foto PRINCIPAL del tour (la primera de tour_images, no un placeholder
    // estirado a pantalla completa — ver la nota larga junto al hero, más
    // abajo, para el caso sin foto). $hasRealPhoto se calcula sobre la
    // relación cargada ($tour['images']), NUNCA sobre $galleryImages (esa sí
    // cae al placeholder de 1 foto para que la galería nunca quede vacía —
    // ver más abajo — así que usarla acá habría hecho creer que TODO tour
    // "tiene foto").
    $hasRealPhoto = $tour['images']->isNotEmpty();
    $heroImage = $hasRealPhoto ? $tour['images']->first() : null;

    // Adelantado desde más abajo (antes vivía junto al bloque de galería):
    // el hero también imprime el H1, así que necesita este atributo antes.
    $fallbackLangAttr = $contentFallbackLocale ? str_replace('_', '-', $contentFallbackLocale) : null;
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

    {{-- ============ HERO ============
         Pase cinematográfico (2026-09-18). Antes: banda plana --sand con
         solo las migas de pan; título/resumen vivían más abajo, dentro de
         la rejilla. Ahora: hero a sangre con la foto PRINCIPAL del tour,
         migas, título, duración/dificultad (avance rápido) y precio — el
         detalle completo (CTA, punto de encuentro, resumen) sigue
         exactamente donde estaba, debajo. Nunca dos <h1>: el título vive
         SOLO acá; la columna de la rejilla ya no repite el suyo.

         Con foto: mismo tratamiento que la cabecera cinematográfica del
         índice (--scrim-hero + parallax sutil, apagado bajo 1024px/puntero
         grueso/reduced-motion vía la infraestructura existente).

         Sin foto (caso frecuente en este catálogo — ver el aviso del
         encargo): NUNCA el placeholder SVG estirado a pantalla completa.
         Se resuelve con el tratamiento gráfico de marca que ya usa el resto
         del sitio para bloques oscuros sin foto (.weave-dark +
         --ink-surface, el mismo que la banda CTA de "más tours en este
         destino" más abajo) — geometría abstracta derivada de --brand-h,
         nunca una foto que no existe. --}}
    <x-seo.breadcrumb-jsonld :items="$breadcrumbItems" />

    <section
        @class([
            'relative isolate flex flex-col justify-end overflow-hidden bg-ink-surface',
            'min-h-[62svh] sm:min-h-[70svh] lg:min-h-[76svh]' => $hasRealPhoto,
            'weave-dark min-h-[46svh] sm:min-h-[52svh]' => ! $hasRealPhoto,
        ])
    >
        @if($hasRealPhoto)
            <div class="photo scrim-hero absolute inset-0" aria-hidden="true">
                {{-- Velocidad subida de 0.1 a 0.2 (2026-09-18): la cabecera
                     ocupa casi todo el viewport, así que la ventana de scroll
                     donde el visitante la ve completa es corta; con 0.1 el
                     desplazamiento en ese tramo era casi nulo. El clamp del
                     12% del alto del marco no cambia. --}}
                <div class="parallax-frame" data-parallax="0.2">
                    <x-ui.picture
                        src="{{ $heroImage['src'] }}"
                        alt=""
                        sizes="100vw"
                        loading="eager"
                        fetchpriority="high"
                        decoding="sync"
                        position="center 45%"
                        imgClass="h-full w-full object-cover"
                    />
                </div>
            </div>
        @endif

        <div class="shell relative z-10 pb-10 pt-24 sm:pb-14 sm:pt-28 lg:pb-16">
            <x-ui.breadcrumbs :items="$breadcrumbItems" tone="dark" class="mb-6" />

            @if($tour['destination'])
                <a href="{{ route('destinations.show', $tour['destination']['slug']) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-on-dark-2 transition-colors hover:text-white">
                    {!! $iconPin !!}{{ $tour['destination']['name'] }}
                </a>
            @endif

            <h1 class="mt-3 max-w-3xl font-display text-hero font-semibold text-white" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $tour['title'] }}</h1>

            @if($tour['duration_label'] || $tour['difficulty'])
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    @if($tour['duration_label'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-sm text-white ring-1 ring-white/25 backdrop-blur-sm">
                            {!! $iconClock !!}<span class="sr-only">{{ __('site.tours.show.duration_label') }}:</span> {{ $tour['duration_label'] }}
                        </span>
                    @endif
                    @if($tour['difficulty'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-sm text-white ring-1 ring-white/25 backdrop-blur-sm">
                            {!! $iconGauge !!}<span class="sr-only">{{ __('site.tours.show.difficulty_label') }}:</span> {{ __('tours.difficulty.'.$tour['difficulty']->value) }}
                        </span>
                    @endif
                </div>
            @endif

            <x-ui.money
                :pen-cents="$tour['price_pen_cents']"
                :usd-cents="$tour['price_usd_cents']"
                :prefix="__('site.tours.show.price_prefix')"
                tone="dark"
                class="mt-7 font-display text-h2 font-semibold text-white"
            />
        </div>
    </section>

    <section class="bg-surface">
        <div class="shell section">
            {{--
                Defecto C (validacion de la clienta, 2026-09-14): en movil el
                precio y "Reservar" caian DESPUES de los cuatro parrafos de
                descripcion, a 1266px del inicio del documento (medido a 360;
                1293px en ingles). Casi dos pantallas de scroll antes de saber
                cuanto cuesta.

                La ficha pasa a TRES items de rejilla en vez de dos, para que
                el orden del DOM -- el que oyen lector de pantalla y tabulador
                -- coincida con el orden visual en movil:

                    galeria + resumen  ->  PRECIO + CTA  ->  descripcion

                (el titulo se mudo al hero de arriba en el pase
                cinematografico, 2026-09-18 -- un solo H1 por pagina, nunca
                dos.)

                En escritorio se recolocan con col-start/row-start, asi que la
                columna lateral se queda exactamente donde estaba. El hueco
                vertical baja a 0 en lg y la descripcion recupera su mt-6 de
                siempre, para no introducir un espaciado nuevo.
            --}}
            <div class="grid gap-x-10 gap-y-10 lg:grid-cols-[3fr_2fr] lg:items-start lg:gap-x-14 lg:gap-y-0">
            {{-- min-w-0: sin esto el item de rejilla toma el min-content de la
                 tira de miniaturas (5 x 80px + huecos = 440px) y la ficha
                 desbordaba 96px a 360. Medido con getBoundingClientRect. --}}
            <div class="min-w-0 lg:col-start-1 lg:row-start-1">
                <x-ui.gallery :images="$galleryImages" :label="__('site.ui.gallery.nav_label', ['title' => $tour['title']])" />

                {{-- Objetivo 2 (lote i18n): lang="es" honesto en cada bloque
                     de contenido de catalogo cuando este tour todavia no
                     tiene su traduccion al locale de la URL -- nunca se
                     finge que este texto esta en el idioma de la pagina.
                     Ver ResolvesBySlugByLocale y el aviso de abajo.
                     $fallbackLangAttr ahora se calcula arriba, junto al
                     resto del @php de cabecera (lo necesita tambien el H1
                     del hero). --}}
                <x-ui.content-fallback-notice :locale="$contentFallbackLocale" />

                <p class="mt-6 max-w-2xl text-lead text-text-2" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $tour['summary'] }}</p>

            </div>

            {{-- Panel de reserva. Antes era una caja casi vacia (precio +
                 boton) que dejaba una columna entera en blanco, mientras
                 duracion, dificultad y punto de encuentro estaban repartidos
                 por la columna izquierda. Ahora viven aca, que es donde se
                 decide la compra.

                 El id lo usa la barra de reserva de movil del final de esta
                 vista para saber si este panel ya esta en pantalla. --}}
            <aside id="panel-reserva" class="min-w-0 lg:col-start-2 lg:row-start-1 lg:row-span-2 lg:sticky lg:top-24">
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
                        {{-- DEF-A: el enlace lleva el tour (?tour=<slug>) y
                             el ancla al formulario; la línea de abajo cierra
                             la brecha entre lo que promete el botón y lo que
                             ocurre al pulsarlo (no hay motor de reservas en
                             este alcance). Ver TourController::show(). --}}
                        <x-ui.button :href="$reserveUrl" class="w-full justify-center px-6 py-3 text-base">
                            {{ __('site.tours.show.cta_reserve') }}
                        </x-ui.button>
                        <p class="mt-2 text-center text-xs text-text-2">
                            {{ __('site.tours.show.cta_reserve_hint') }}
                        </p>

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

            {{-- Descripcion larga. Tercer item de la rejilla: en movil va
                 DESPUES del panel de precio (ver el comentario de la rejilla),
                 en escritorio vuelve a la columna izquierda, bajo el resumen,
                 con el mismo mt-6 de antes.

                 "description" se edita con Filament\Forms\Components\RichEditor
                 (ver TourForm) -- es HTML de la clienta, no texto plano. Se
                 escapaba y mostraba los tags en pantalla;
                 App\Support\Html\RichTextSanitizer lo limpia (whitelist exacta
                 de la toolbar del editor) antes de imprimirlo. --}}
            <div class="prose-pv min-w-0 max-w-2xl text-base leading-relaxed text-text-2 lg:col-start-1 lg:row-start-2 lg:mt-6" data-reveal @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>
                {!! RichTextSanitizer::sanitize($tour['description']) !!}
            </div>
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
                <x-ui.section-title as="h2" data-reveal>{{ __('site.tours.show.itinerary_title') }}</x-ui.section-title>

                <x-ui.content-fallback-notice :locale="$itineraryFallbackLocale" />

                <div @if($itineraryLangAttr) lang="{{ $itineraryLangAttr }}" @endif>
                    @foreach($tour['itinerary'] as $day)
                        {{-- Mismo criterio que "description" arriba: HTML de
                             la clienta (RichEditor), saneado antes de
                             imprimirse. Revelado escalonado directo sobre el
                             <details> (no envuelto en un div propio: envolver
                             cada item rompe los selectores first:/last: que
                             usa x-ui.faq-item para el borde y el padding,
                             porque cada <details> pasaría a ser hijo único de
                             su propio contenedor). Tope en 5 pasos, igual que
                             Home: con itinerarios largos el retraso no crece
                             sin límite. --}}
                        <x-ui.faq-item
                            :question="$day['title']"
                            data-reveal
                            style="--reveal-delay:{{ min($loop->index, 5) * 70 }}ms"
                        >{!! RichTextSanitizer::sanitize($day['description']) !!}</x-ui.faq-item>
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
                    <div class="rounded-panel border border-line bg-surface p-6 shadow-e1" data-reveal @if($inclusionsLangAttr) lang="{{ $inclusionsLangAttr }}" @endif>
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
                    <div class="rounded-panel border border-line bg-surface p-6 shadow-e1" data-reveal style="--reveal-delay:80ms" @if($exclusionsLangAttr) lang="{{ $exclusionsLangAttr }}" @endif>
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

    {{--
        Defecto C, segunda mitad. Reordenar sube el precio de 1266px a ~570px
        en movil, pero sigue por debajo del pliegue: la clienta dice que su
        gente "no ve ni cuanto cuesta", y eso solo se resuelve si el precio
        esta en pantalla desde el primer momento.

        Por que una barra fija y no duplicar el panel en el flujo:
        - No hay dos bloques compitiendo en la pagina: la barra se esconde en
          cuanto el panel de reserva de verdad entra en pantalla, y tambien al
          llegar al pie, para no taparlo.
        - No añade ninguna parada de tabulador ni una segunda lectura para el
          lector de pantalla: es un atajo VISUAL a un control que ya existe
          antes en el documento y es alcanzable por teclado. De ahi
          aria-hidden + tabindex="-1" (sin el tabindex, un enlace enfocable
          dentro de aria-hidden si seria un defecto de accesibilidad).
        - Solo movil y tablet (lg:hidden): en escritorio el panel lateral ya
          esta visible al cargar y la clienta lo aprobo tal cual.
        - Sin JS la barra se queda visible: sigue cumpliendo su funcion.

        Destino y rotulo salen de las MISMAS fuentes que el CTA del panel
        ($reserveUrl, que arma TourController::show, y la clave de idioma
        cta_reserve): no hay una segunda URL que se pueda quedar atras.
    --}}
    <div
        x-data="{ panelVisible: false, pieVisible: false }"
        x-init="
            const mirar = (sel, margen, fn) => {
                const el = document.querySelector(sel);
                if (!el) return;
                new IntersectionObserver(([e]) => fn(e.isIntersecting), { rootMargin: margen }).observe(el);
            };
            mirar('#panel-reserva', '0px', (v) => panelVisible = v);
            mirar('footer', '0px 0px 96px 0px', (v) => pieVisible = v);
        "
        x-show="!panelVisible && !pieVisible"
        x-transition.opacity.duration.150ms
        aria-hidden="true"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 shadow-e3 backdrop-blur-sm lg:hidden"
    >
        <div class="shell flex items-center gap-4 py-3">
            <x-ui.money
                :pen-cents="$tour['price_pen_cents']"
                :usd-cents="$tour['price_usd_cents']"
                :prefix="__('site.tours.show.price_prefix')"
                class="min-w-0 font-display text-h3 font-semibold text-ink"
            />
            <x-ui.button
                :href="$reserveUrl"
                tabindex="-1"
                class="ml-auto shrink-0 px-5 py-2.5"
            >{{ __('site.tours.show.cta_reserve') }}</x-ui.button>
        </div>
    </div>

</x-layout>
