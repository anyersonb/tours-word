@php
    use App\Models\Destination;
    use App\Models\Experience;
    use App\Models\Tour;
    use App\Support\PlaceholderImage;

    /**
     * Datos reales de MySQL (B5). Cada colección se resuelve vacía si no hay
     * filas publicadas — ninguna sección asume 3/4 elementos como el mockup:
     * ver los @if(...->isNotEmpty()) de cada bloque de abajo.
     */
    $featuredTours = Tour::query()
        ->published()
        ->featured()
        ->ordered()
        ->with(['destination', 'experiences', 'images'])
        ->get();

    $destinations = Destination::query()->where('is_published', true)->orderBy('order')->get();
    $experiences = Experience::query()->where('is_published', true)->orderBy('order')->get();

    /**
     * Mosaico Roavio (port fiel, 2026-09-20): 6 tiles con 3 anchos distintos
     * bajo el hero. NO es contenido nuevo — son las mismas 3 primeras
     * $destinations y las mismas 3 primeras $experiences que ya bajan a las
     * secciones de abajo, sólo que acá se presentan como vistazo fotográfico
     * a sangre completa. Cero @php de datos nuevos, cero contenido inventado.
     *
     * Posición por SLOT, no por nombre/slug: cada slot fija qué colección y
     * qué índice de esa colección ocupa, con el object-position elegido
     * mirando esa foto real en el navegador (nunca "center" a ciegas — ver
     * encargo). Si el catálogo cambiara de orden, el slot sigue mostrando lo
     * que hoy ocupe esa posición: no se buscan slugs concretos.
     */
    $mosaicSlots = [
        // 'wide' (556x634 a 1440, la tile más grande) — Valle Sagrado: foto
        // panorámica (2.80:1), es la más espectacular del banco; se le da el
        // tile de mayor peso visual aunque igual se recorte a retrato.
        ['col' => $destinations, 'type' => 'destination', 'i' => 1, 'position' => 'center 72%'],
        // 'narrow' (296x634, la más angosta) — Cultura: composición
        // diagonal/vertical (telar), lee bien en un hueco muy estrecho.
        ['col' => $experiences, 'type' => 'experience', 'i' => 2, 'position' => '68% 45%'],
        // Par apilado A (296x315 x2)
        ['col' => $destinations, 'type' => 'destination', 'i' => 0, 'position' => 'center 58%'], // Cusco
        ['col' => $experiences, 'type' => 'experience', 'i' => 0, 'position' => '76% 58%'],       // Trekking: grupo a la derecha del encuadre
        // Par apilado B (276x315 x2)
        ['col' => $destinations, 'type' => 'destination', 'i' => 2, 'position' => 'center 68%'], // Arequipa
        ['col' => $experiences, 'type' => 'experience', 'i' => 1, 'position' => 'center 48%'],   // Gastronomía
    ];
    $mosaicTiles = collect($mosaicSlots)
        ->map(function ($slot) {
            $model = $slot['col']->get($slot['i']);

            if (! $model) {
                return null;
            }

            return [
                'model' => $model,
                'type' => $slot['type'],
                'position' => $slot['position'],
                'href' => $slot['type'] === 'destination'
                    ? route('destinations.show', $model->slug)
                    : route('experiences.show', $model->slug),
            ];
        })
        ->filter()
        ->values();
    // Geometría completa solo con las 6 piezas reales; con menos, cae a una
    // rejilla simple (mismo criterio que "Tours destacados": ninguna sección
    // asume una cantidad fija de elementos).
    $hasFullMosaic = $mosaicTiles->count() === 6;

    // Paleta cíclica SOLO para el placeholder decorativo, usado cuando el
    // registro todavía no tiene foto real cargada (D · lote 1/etapa D:
    // Destination/Experience ya tienen columna cover_image_path desde el
    // esquema del lote 1, pero la base de hoy no tiene ninguna cargada, y
    // tour_images sigue vacía).
    $photoPalette = ['1b6949', '2c6fa8', '93590c', 'c2410c', '135338'];

    $experienceIcons = [
        'trekking' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="m3 20 6-10 4 6 2-3 6 7H3Z"/></svg>',
        'gastronomia' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M6 3v7a2 2 0 0 0 2 2 2 2 0 0 0 2-2V3M8 12v9M17 3c-1.5 0-3 1.5-3 4s1.5 4 3 4v9"/></svg>',
        'cultura' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M4 8c0-3 3.5-5 8-5s8 2 8 5-3.5 9-8 9-8-6-8-9Z"/><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><path d="M9 13c1 1 5 1 6 0"/></svg>',
    ];
    $defaultExperienceIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-2 6-6 2 2-6 6-2Z"/></svg>';

    $heroTrustIcons = [
        'safe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>',
        'guides' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M8.5 13 7 21l5-2.5L17 21l-1.5-8"/></svg>',
        'personalized' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M12 20s-7-4.35-9.5-8.5C.7 8 2 4.5 5.5 4a4.8 4.8 0 0 1 6.5 2 4.8 4.8 0 0 1 6.5-2C22 4.5 23.3 8 21.5 11.5 19 15.65 12 20 12 20Z"/></svg>',
        'prices' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M20 12 12.5 19.5a2 2 0 0 1-2.83 0l-6.17-6.17a2 2 0 0 1 0-2.83L11 3h9v9Z"/><circle cx="15.5" cy="7.5" r="1.25"/></svg>',
        'sustainable' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M5 21c8 0 14-6 14-16-10 0-16 6-16 14 0 .7.05 1.35.14 2Z"/><path d="M5 21c3-4 6-7 12-11"/></svg>',
    ];

    $playIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m10 9 5 3-5 3V9Z" fill="currentColor" stroke="none"/></svg>';
    $headsetIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="2.5" y="13" width="4" height="6" rx="1.5"/><rect x="17.5" y="13" width="4" height="6" rx="1.5"/><path d="M20 19v1a3 3 0 0 1-3 3h-3"/></svg>';

    $whyUsIcons = [
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-2 6-6 2 2-6 6-2Z"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M12 20s-7-4.35-9.5-8.5C.7 8 2 4.5 5.5 4a4.8 4.8 0 0 1 6.5 2 4.8 4.8 0 0 1 6.5-2C22 4.5 23.3 8 21.5 11.5 19 15.65 12 20 12 20Z"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>',
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/></svg>',
    ];

    /**
     * Slider de hero (pase cinematográfico, 2026-09-18). Las tres fotos YA
     * EXISTÍAN en public/images/site/ con sus derivadas WebP responsive
     * (640/1024/1440/1920) — no se agregó ninguna imagen nueva. El "position"
     * es el foco vertical de cada foto (object-position), elegido mirando
     * cada una: Machu Picchu deja el horizonte en el tercio alto (igual que
     * el pase anterior); Valle Sagrado empuja hacia abajo para no dejar el
     * cielo muy claro justo detrás del titular; Cordillera centra los picos
     * nevados. El orden acá DEBE coincidir con site.home.hero.slides (mismo
     * índice = misma foto).
     *
     * "positionMobile" (cierre del hero, 2026-09-21): a 375 el marco de V2
     * cae a 0.449:1 (836/375, medido) — mucho más angosto que el AR nativo
     * de las 3 fotos (1.478 / 2.391 / 1.692), así que object-fit:cover
     * SIEMPRE muestra el alto completo de la foto y recorta por los lados;
     * el eje Y del "position" de escritorio queda inerte a este ancho (no
     * hay margen vertical que mover) y el que manda es X. Se recalculó X
     * mirando cada foto en el navegador a 375 (nunca reusando el valor de
     * escritorio a ciegas — mismo criterio que el mosaico del lote 0):
     *   - Machu Picchu: X 52% dentro de la franja visible (~30% del ancho
     *     nativo) mete el pico de Huayna Picchu completo + el arranque de
     *     las terrazas/ruinas a su derecha.
     *   - Valle Sagrado: X 75% — a esta foto (panorámica 2.39:1) solo
     *     queda visible un ~19% del ancho nativo; centrado (50%) caía en
     *     una montaña genérica, 75% mete el pueblo + el valle cultivado +
     *     el río, que es el contenido con más lectura de la foto.
     *   - Cordillera-río: X 50% (centro) — el conjunto de picos nevados y
     *     el río que converge hacia el centro ya caen ahí, sin ajuste.
     */
    $heroSlides = [
        ['file' => 'hero-machupicchu-amanecer', 'position' => 'center 42%', 'positionMobile' => '52%'],
        ['file' => 'hero-valle-sagrado-panoramica', 'position' => 'center 60%', 'positionMobile' => '75%'],
        ['file' => 'hero-cordillera-rio', 'position' => 'center 46%', 'positionMobile' => 'center'],
    ];
    // ['alt' => ..., 'label' => ...] por diapositiva. "alt" queda sin usar en
    // el <img> (las tres fotos son decorativas: el titular ya dice de qué
    // trata la página, y cada foto ya se describe en el anunciador aria-live
    // y en el aria-label de su botón indicador — un alt real duplicaría esa
    // lectura). Se deja declarado para que la pasada B lo reutilice donde la
    // foto SÍ sea contenido con significado propio.
    $heroSlideLabels = __('site.home.hero.slides');

    /**
     * Port Roavio, lote 1 (2026-09-21): testimonios y aliados/sellos.
     * CONTENIDO ESTÁTICO QUEMADO EN LA VISTA a propósito — ninguno de los
     * dos tiene modelo en el proyecto todavía (ver encargo: "no inventes un
     * modelo ni una migración chiquita"). Cada bloque va marcado
     * "DEMO: pendiente modelo" en su propio comentario más abajo.
     *
     * Testimonios: nombres genéricos (nombre + inicial de apellido) y
     * ningún avatar de banco/IA — B2 (pase anterior) ya había descartado
     * esta sección por eso mismo. Acá el avatar es la inicial sobre un
     * cuadrado de color (x-ui.testimonial-card variant="tile"), nunca una
     * cara inventada.
     */
    $testimonials = collect(__('site.home.testimonials.items'))
        ->map(fn ($t, $i) => $t + ['tone' => $photoPalette[$i % count($photoPalette)]]);

    /**
     * APAGADA (2026-09-21, orden explícita de Anyerson: "deja el testimonio
     * apagado"). La sección se había reactivado en el lote 1 con 6
     * testimonios DEMO inventados (ver comentario arriba). Se apaga de
     * nuevo -- MISMO criterio que la decisión original del proyecto (B2,
     * antes del lote 1): sin modelo de reseñas reales, no se publica
     * ninguna cara/nombre inventado, ni siquiera con avatar de inicial.
     *
     * La maqueta (markup, x-ui.testimonial-card variant="tile", el
     * carrusel) NO se borra: sigue completa más abajo, envuelta en el
     * @if($testimonialsEnabled) de la sección "6. LO QUE DICEN NUESTROS
     * VIAJEROS". Para encenderla cuando la clienta entregue reseñas reales:
     *   1. Cambiar $testimonialsEnabled a true.
     *   2. Reemplazar $testimonials (arriba) por datos reales -- lo ideal
     *      es un modelo Review con name/quote/origin/avatar, no seguir
     *      quemando el array de lang/{es,en}/site.php#testimonials.items.
     *   3. Borrar el comentario "DEMO: pendiente modelo de reseñas" de la
     *      sección y de los lang files.
     */
    $testimonialsEnabled = false;

    /**
     * Aliados + sellos oficiales: SIN ARCHIVOS REALES en el proyecto (ni
     * logos de aliados ni el sello RNAVT/MINCETUR — ver búsqueda en
     * public/images, no hay ninguno). Se maqueta el hueco con un wordmark
     * de texto neutro, nunca con un logo inventado ni un sello con
     * apariencia oficial (regla dura del encargo). Los dos últimos son
     * justamente los sellos regulatorios peruanos, marcados aparte.
     */
    $partners = [
        ['label' => 'Aliado 01'], ['label' => 'Aliado 02'], ['label' => 'Aliado 03'],
        ['label' => 'Aliado 04'], ['label' => 'Aliado 05'], ['label' => 'Aliado 06'],
        ['label' => 'RNAVT', 'seal' => true], ['label' => 'MINCETUR', 'seal' => true],
    ];
@endphp
<x-layout :title="__('site.home.meta.title')" :description="__('site.home.meta.description')">

    {{--
        ============ 1. HERO ============
        Pase cinematográfico (2026-09-18). Antes: una sola foto fija con
        scrim. Ahora: slider de 3 fotos con Ken Burns + fundido cruzado, alto
        real 100svh (min-height, no height: si el contenido de un idioma más
        largo o un móvil muy angosto necesita más espacio, crece en vez de
        recortar — nunca 100vh, que en móvil incluye la barra del navegador
        y corta el pie del hero).

        El scrim NO es decoración: es lo que hace que el texto blanco pase AA
        sobre CUALQUIERA de las tres fotos — se midió el peor píxel del
        compuesto foto+scrim de cada una, no solo de la primera (ver informe
        de cierre para los tres contrastes).

        Autoavance pausable (botón dedicado + se detiene solo con
        hover/foco/pestaña oculta), navegable por teclado (los indicadores
        son <button> reales) y quieto del todo con prefers-reduced-motion
        (ver heroSlider() en app.js): el temporizador nunca arranca, y sin
        JS el <picture> de la primera diapositiva ya es la única visible
        (opacity:1 por defecto en app.css, ver ".hero-slide:first-child",
        anulado en cuanto Alpine marca #hero como listo).

        DIFERIDO REAL de las diapositivas 2 y 3 (SEO, 2026-09-18): medido con
        captura de red, las 3 fotos se descargaban siempre — opacity:0 sobre
        position:absolute;inset:0 sigue contando como "en viewport" para el
        lazy-loading nativo, así que loading="lazy"/fetchpriority="low" no
        servían de nada (~478 KB de las 2 diapositivas invisibles a 1920w,
        más que el peso declarado de toda la home). Ahora sus <picture> viven
        inertes dentro de <template> — el navegador NO dispara ninguna
        petición por su contenido — y heroSlider() los clona al DOM real en
        el primer avance real del slider (autoplay, flecha o punto), justo
        antes de activarlos (ver loadSlide()/go() en app.js). La diapositiva
        1 no se toca: sigue eager/fetchpriority="high" fuera del <template>.
    --}}
    <section
        id="hero"
        {{--
            Full-bleed real: sin .shell-boxed, sin radio, sin margen —
            w-full a secas, hijo directo de <main>, así que ocupa el 100%
            del ancho disponible sin el patrón 100vw (ese sí puede
            desbordar si hay scrollbar vertical; w-full de un bloque normal
            nunca lo hace, verificado con documentElement.scrollWidth a 375
            — ver informe).

            Alto: min-h 100svh menos el header. DECISIÓN DEL CLIENTE
            (2026-09-21): "vamos con la versión V2 · Pantalla completa" —
            cierra la ambigüedad "fullhero apaisado" que había dejado dos
            variantes vivas en el código (V1, banda cinematográfica por
            aspect-ratio, y V2, pantalla completa; ver
            docs/rediseno-2026/08-lote0-roavio.md §4 para el historial). V1
            se retiró del código — sigue en git si hace falta revisarla.
        --}}
        class="relative isolate flex w-full flex-col bg-ink-surface min-h-[calc(100svh-4rem)]"
        x-data="heroSlider({{ count($heroSlides) }})"
        x-init="init()"
        @keydown.left="prev()"
        @keydown.right="next()"
        role="region"
        aria-label="{{ __('site.home.hero.carousel_label') }}"
    >
        <div class="absolute inset-0" aria-hidden="true">
            @foreach($heroSlides as $i => $slide)
                <div class="hero-slide" :class="active === {{ $i }} ? 'is-active' : ''">
                    <div class="photo scrim-hero-h h-full w-full" data-hero-photo="{{ $i }}">
                        @if($i === 0)
                            {{-- LCP: única foto servida de entrada, prioridad alta. --}}
                            <x-ui.picture
                                src="{{ asset('images/site/'.$slide['file'].'.jpg') }}"
                                alt=""
                                sizes="100vw"
                                loading="eager"
                                fetchpriority="high"
                                decoding="sync"
                                :position="$slide['position']"
                                :position-sm="$slide['positionMobile']"
                                imgClass="hero-slide__img h-full w-full object-cover"
                            />
                        @else
                            {{-- Inerte a propósito: dentro de <template> el navegador
                                 no la pide. heroSlider() la clona a data-hero-photo
                                 en el primer avance que la active (ver app.js). --}}
                            <template data-hero-template="{{ $i }}">
                                <x-ui.picture
                                    src="{{ asset('images/site/'.$slide['file'].'.jpg') }}"
                                    alt=""
                                    sizes="100vw"
                                    :position="$slide['position']"
                                    :position-sm="$slide['positionMobile']"
                                    imgClass="hero-slide__img h-full w-full object-cover"
                                />
                            </template>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Anunciador para lector de pantalla: cambia con el slide activo,
             sin depender de un alt de foto (decorativa). --}}
        <p class="sr-only" role="status" aria-live="polite">
            @foreach($heroSlideLabels as $i => $slideLabel)
                <span x-show="active === {{ $i }}" x-cloak>{{ $slideLabel['label'] }}</span>
            @endforeach
        </p>

        {{-- Full-bleed real: la sección ya no pone su propio padding-inline
             (no hay .shell-boxed), así que el gutter horizontal del
             contenido vuelve a vivir en ESTE .shell, igual que antes del
             port Roavio. --}}
        <div class="relative z-10 flex flex-1 flex-col justify-center py-28 sm:py-32 lg:py-24">
            <div class="shell">
                <div class="max-w-xl lg:max-w-2xl">
                    {{-- B3: no imprime nada sin Setting::get('rnavt_number') --}}
                    <x-ui.mincetur-badge class="mb-5" />

                    <h1 class="font-display text-hero font-semibold text-white">
                        {{ __('site.home.hero.title_before') }}
                        {{-- Defecto D (pase anterior): el acento era
                             text-brand-100 y se perdía sobre la zona clara de
                             la foto. Ver .accent-on-photo en app.css, con los
                             contrastes medidos: el énfasis es de PESO, no de
                             color, así que sigue funcionando igual sobre las
                             tres fotos nuevas. --}}
                        <span class="accent-on-photo">{{ __('site.home.hero.title_highlight') }}</span>
                        {{ __('site.home.hero.title_after') }}
                    </h1>

                    <p class="mt-5 max-w-xl text-lead text-on-dark-2">
                        {{ __('site.home.hero.subtitle') }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <x-ui.button href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}" class="px-6 py-3 text-base shadow-e2">
                            {{ __('site.home.hero.cta_primary') }}
                        </x-ui.button>
                        {{-- Sobre foto, la variante "ghost" (texto verde sin
                             fondo) no se lee: el secundario pasa a contorno
                             blanco, que sí contrasta contra el scrim. --}}
                        <a
                            href="{{ Route::has('destinations.index') ? route('destinations.index') : '#' }}"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/50 bg-ink-surface/65 px-6 py-3 text-base font-medium text-white backdrop-blur-sm transition-colors hover:bg-white hover:text-ink"
                        >
                            {!! $playIcon !!}
                            {{ __('site.home.hero.cta_secondary') }}
                        </a>
                    </div>
                </div>

                {{-- B1: x-ui.stats-strip no imprime nada sin Setting stat_*.
                     Sin datos no queda ninguna caja vacía. --}}
                <x-ui.stats-strip class="mt-10 max-w-3xl" />
            </div>
        </div>

        {{-- Pie del hero: franja de confianza + indicadores del slider en la
             misma fila (envuelven juntos en móvil, "ml-auto" en vez de
             justify-between para que el grupo de indicadores no quede
             anclado a la izquierda si la fila envuelve), y el cue de scroll
             debajo. Todo dentro del flujo normal del section (flex-col), no
             absolute: así nunca se superpone con el contenido si un idioma
             más largo empuja el alto del hero por encima de 100svh. --}}
        <div class="relative z-10">
            <div class="border-t border-white/15 bg-ink-surface/55 backdrop-blur-sm">
                {{--
                    Franja de confianza: en escritorio es una fila que envuelve
                    (flex-wrap). En móvil, envolver 5 pastillas la convertía en
                    5 filas apiladas — el hero pasaba de una pantalla a casi
                    dos solo por esto (medido: ~330px extra a 375px), en
                    contra del punto "full-screen" del encargo. Se resuelve
                    igual que el carrusel de tarjetas: una tira que se
                    desliza horizontal (scroll-x, sin scrollbar visible), sin
                    quitar ninguna insignia.
                --}}
                <div class="shell flex flex-col gap-3 py-4 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-8 sm:gap-y-3">
                    <div class="flex gap-x-6 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:flex-wrap sm:gap-x-8 sm:gap-y-3 sm:overflow-visible sm:pb-0 [&::-webkit-scrollbar]:hidden">
                        @foreach($heroTrustIcons as $key => $icon)
                            <x-ui.trust-badge :icon="$icon" tone="dark" class="shrink-0">{{ __('site.home.hero.trust.'.$key) }}</x-ui.trust-badge>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-3 sm:ml-auto">
                        <div class="flex items-center gap-1.5" role="group" aria-label="{{ __('site.home.hero.carousel_label') }}">
                            @foreach($heroSlideLabels as $i => $slideLabel)
                                <button
                                    type="button"
                                    @click="go({{ $i }})"
                                    class="hero-dot focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                    :class="active === {{ $i }} ? 'is-active' : ''"
                                    :aria-current="active === {{ $i }} ? 'true' : 'false'"
                                    aria-label="{{ __('site.home.hero.go_to_slide', ['label' => $slideLabel['label']]) }}"
                                >
                                    <span class="hero-dot__mark" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>

                        <button
                            type="button"
                            @click="toggle()"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-white/80 transition-colors hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                            :aria-pressed="paused ? 'true' : 'false'"
                            :aria-label="paused ? '{{ __('site.home.hero.play') }}' : '{{ __('site.home.hero.pause') }}'"
                        >
                            <svg x-show="!paused" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M8 5v14M16 5v14"/></svg>
                            <svg x-show="paused" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="m10 9 5 3-5 3V9Z" fill="currentColor" stroke="none"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cue de scroll: enlace real a la siguiente sección (funciona
                 sin JS), oculto desde el móvil más chico para no competir
                 por espacio vertical con la franja de confianza. El rebote
                 se anula solo con prefers-reduced-motion (ver app.css).
                 Port Roavio: apunta al mosaico (la sección que sigue de
                 verdad ahora) en vez de saltárselo hacia "tours-destacados".
                 Si el mosaico no tiene piezas y no se renderiza (catálogo
                 vacío), el navegador simplemente no encuentra el ancla y no
                 hace nada — no hace falta un condicional acá. --}}
            <div class="hidden justify-center pb-5 sm:flex">
                <a
                    href="#mosaico"
                    class="hero-scroll-cue inline-flex flex-col items-center gap-1 rounded-full px-2 py-1 text-white/80 transition-colors hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                >
                    <span class="text-xs font-medium uppercase tracking-wide">{{ __('site.home.hero.scroll_cue') }}</span>
                    <svg class="hero-scroll-cue__icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{--
        ============ 1b. MOSAICO (port Roavio, 2026-09-20) ============
        La banda que rompe la planitud: a sangre completa (.shell-bleed,
        1432px a 1440 de viewport — casi tocando el borde, justo después de
        un hero BOXEADO. Ese contraste boxed→sangre es el 80% del efecto
        Roavio, no un detalle (ver encargo).

        6 tiles, 3 anchos: 1 angosta (296px a 1440), 1 ancha (556px), y dos
        pares apilados (296px y 276px, dos filas de 315px cada una). Se logra
        con UN SOLO grid: 4 columnas por fr literal (296fr/556fr/296fr/276fr
        — el valor exacto medido en Roavio, no una fracción redondeada) y
        row-span-2 en las dos columnas que van "altas". Cero JS.

        Sin H2 visible: Roavio tampoco lleva uno ahí, es un respiro
        fotográfico entre el hero y "Tours destacados" — pero sigue siendo
        una región con nombre para lectores de pantalla.

        Contenido: NO son fotos nuevas. Son las mismas 3 $destinations y las
        mismas 3 $experiences que bajan a sus propias secciones más abajo
        ($mosaicTiles arriba solo las reordena por slot) — cero contenido
        inventado, cero query nueva.

        Radio: rounded-tile (10px, --r-tile) en vez de rounded-card/panel
        del resto del sitio — es el radio medido en Roavio (91% de sus
        elementos), token nuevo en tokens.css que no reemplaza a los otros.
    --}}
    @if($mosaicTiles->isNotEmpty())
    <section id="mosaico" aria-label="{{ __('site.home.showcase.aria_label') }}" class="bg-surface pb-10 pt-2 sm:pb-12 lg:pb-16">
        <div class="shell-bleed">
            @if($hasFullMosaic)
                {{--
                    Responsive en UN solo grid, sin duplicar markup:
                    - <640: 2 columnas parejas, cada tile aspect-[3/4].
                    - 640-1023: 3 columnas parejas (mismo aspecto).
                    - >=1024: la retícula asimétrica de Roavio — 4 columnas
                      por fr LITERAL (296/556/296/276, el valor medido, no
                      una fracción redondeada) y las dos columnas "altas"
                      (slot 0 = ancha, slot 1 = angosta) pasan a row-span-2
                      SOLO en este breakpoint (lg:row-span-2, nunca
                      row-span-2 a secas: a 2-3 columnas ese salto de fila
                      dejaría huecos en la rejilla).
                --}}
                <div
                    class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-2.5 lg:gap-3 lg:[grid-template-columns:296fr_556fr_296fr_276fr] lg:[grid-template-rows:repeat(2,clamp(11.5rem,21.9vw,19.7rem))]"
                >
                    @foreach($mosaicTiles as $i => $tile)
                        @php
                            $isTallSlot = $i === 0 || $i === 1; // wide + narrow: cubren las 2 filas (solo en lg+)
                            $fallbackSize = $tile['type'] === 'destination' ? [480, 600] : [480, 360];
                        @endphp
                        <x-ui.mosaic-tile
                            :image="$tile['model']->coverImageUrl() ?? PlaceholderImage::svg($fallbackSize[0], $fallbackSize[1], $tile['model']->name, $photoPalette[$i % count($photoPalette)])"
                            :image-alt="filled($tile['model']->cover_image_alt) ? $tile['model']->cover_image_alt : $tile['model']->name"
                            :title="$tile['model']->name"
                            :subtitle="\Illuminate\Support\Str::limit($tile['model']->description, 42)"
                            :href="$tile['href']"
                            :position="$tile['position']"
                            sizes="(min-width: 1024px) 40vw, (min-width: 640px) 33vw, 50vw"
                            :class="'aspect-[3/4] lg:aspect-auto '.($isTallSlot ? 'lg:row-span-2' : '')"
                            data-reveal
                            style="--reveal-delay:{{ min($i, 5) * 60 }}ms"
                        />
                    @endforeach
                </div>
            @elseif($mosaicTiles->isNotEmpty())
                {{-- Menos de 6 piezas reales (p. ej. catálogo recién sembrado):
                     la geometría asimétrica de Roavio no tiene sentido con
                     menos elementos, así que cae a una rejilla simple con el
                     mismo tratamiento de foto, en vez de dejar huecos vacíos
                     en la retícula fija. --}}
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-2.5 lg:gap-3">
                    @foreach($mosaicTiles as $i => $tile)
                        @php $fallbackSize = $tile['type'] === 'destination' ? [480, 600] : [480, 360]; @endphp
                        <x-ui.mosaic-tile
                            class="aspect-[3/4]"
                            :image="$tile['model']->coverImageUrl() ?? PlaceholderImage::svg($fallbackSize[0], $fallbackSize[1], $tile['model']->name, $photoPalette[$i % count($photoPalette)])"
                            :image-alt="filled($tile['model']->cover_image_alt) ? $tile['model']->cover_image_alt : $tile['model']->name"
                            :title="$tile['model']->name"
                            :subtitle="\Illuminate\Support\Str::limit($tile['model']->description, 42)"
                            :href="$tile['href']"
                            :position="$tile['position']"
                            sizes="(min-width: 640px) 30vw, 45vw"
                            data-reveal
                            style="--reveal-delay:{{ min($i, 5) * 60 }}ms"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    {{--
        ============ 2. TOURS DESTACADOS — rejilla Roavio ============
        Port Roavio (lote 1, 2026-09-21): en Roavio esta sección es una
        rejilla estática de 8 tarjetas idénticas (col-xl-3, 358px), no un
        carrusel. El carrusel salía porque el catálogo de hoy es chico (3
        tours); auto-fit resuelve las dos cosas a la vez: con pocas tarjetas
        colapsa las columnas vacías y las reparte a lo ancho del contenedor
        (mismo truco que ya usan "Destinos" y "Actividades" más abajo), y
        con 8 o más simplemente sigue agregando filas — cero JS, cero
        carrusel que ocultar/mostrar según la cuenta.

        Primera sección boxed después del mosaico a sangre (regla del
        encargo): .shell-boxed, 1392px — contraste fuerte contra el
        mosaico (a sangre) y contra "Destinos" (.shell, 1280) que sigue
        justo debajo.

        Tarjetas: x-ui.tour-card variant="flat" (radio 10px, SIN
        box-shadow) — variante aditiva nueva del componente; tours/index.blade.php
        y las fichas de tour siguen con el variant="card" de siempre
        (radio grande + sombra), sin tocar.
    --}}
    <section id="tours-destacados" class="bg-surface">
        <div class="shell-boxed section">
            <x-ui.eyebrow data-reveal class="mb-3">{{ __('site.home.featured_tours.eyebrow') }}</x-ui.eyebrow>
            <x-ui.section-title as="h2" data-reveal style="--reveal-delay:60ms">
                <x-slot:action>
                    <x-ui.button variant="link" href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}">
                        {{ __('site.home.featured_tours.cta') }} &rarr;
                    </x-ui.button>
                </x-slot:action>
                {{ __('site.home.featured_tours.title') }}
            </x-ui.section-title>

            @if($featuredTours->isNotEmpty())
                <div class="grid gap-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),1fr))]">
                    @foreach($featuredTours as $tour)
                        <x-ui.tour-card
                            variant="flat"
                            :image="optional($tour->images->first())->url() ?? PlaceholderImage::svg(480, 360, $tour->title, $photoPalette[$loop->index % count($photoPalette)])"
                            :image-alt="$tour->title"
                            :title="$tour->title"
                            :summary="$tour->summary"
                            :duration="$tour->duration_label"
                            :category="optional($tour->experiences->first())->name"
                            :location="optional($tour->destination)->name"
                            :pen-cents="$tour->price_pen_cents"
                            :usd-cents="$tour->price_usd_cents"
                            href="{{ route('tours.show', $tour->slug) }}"
                            sizes="(min-width: 1024px) 22vw, (min-width: 640px) 45vw, 90vw"
                            data-reveal
                            style="--reveal-delay:{{ min($loop->index, 5) * 80 }}ms"
                        />
                    @endforeach
                </div>
            @else
                <x-ui.empty-state data-reveal>{{ __('site.home.empty.tours') }}</x-ui.empty-state>
            @endif
        </div>
    </section>

    {{-- ============ 3. DESTINOS IMPERDIBLES ============ --}}
    <section id="destinos" class="weave bg-sand">
        <div class="shell section">
            <x-ui.eyebrow data-reveal class="mb-3">{{ __('site.home.destinations.eyebrow') }}</x-ui.eyebrow>
            <x-ui.section-title as="h2" data-reveal style="--reveal-delay:60ms">
                <x-slot:action>
                    <x-ui.button variant="link" href="{{ Route::has('destinations.index') ? route('destinations.index') : '#' }}">
                        {{ __('site.home.destinations.cta') }} &rarr;
                    </x-ui.button>
                </x-slot:action>
                {{ __('site.home.destinations.title') }}
            </x-ui.section-title>

            @if($destinations->isNotEmpty())
                {{-- auto-fit con 1fr: las tarjetas LLENAN la fila. El tope de
                     280px anterior las dejaba encogidas y centradas, con un
                     hueco de ~170px a la izquierda del contenedor. --}}
                <div class="grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,15rem),1fr))]">
                    @foreach($destinations as $i => $destination)
                        {{--
                            D · lote 1/etapa D: foto real de catálogo con el
                            placeholder SVG como respaldo (nunca al revés).
                            El alt real viene de cover_image_alt; sin ese dato
                            cae al nombre del destino.
                        --}}
                        <x-ui.destination-card
                            :image="$destination->coverImageUrl() ?? PlaceholderImage::svg(480, 600, $destination->name, $photoPalette[$i % count($photoPalette)])"
                            :image-alt="filled($destination->cover_image_alt) ? $destination->cover_image_alt : $destination->name"
                            :name="$destination->name"
                            :tagline="$destination->description"
                            href="{{ route('destinations.show', $destination->slug) }}"
                            data-reveal
                            style="--reveal-delay:{{ min($i, 5) * 80 }}ms"
                        />
                    @endforeach
                </div>
            @else
                <x-ui.empty-state data-reveal>{{ __('site.home.empty.destinations') }}</x-ui.empty-state>
            @endif
        </div>
    </section>

    {{--
        ============ 4. ¿POR QUÉ ELEGIR VIAJAR CON NOSOTROS? (about Roavio) ============
        Port Roavio (lote 1, 2026-09-21): columnas 5/7, no 50/50 — el
        zig-zag simétrico es tic de plantilla genérica (encargo). Contenedor
        .shell-boxed (1392, no el .shell de 1280 de siempre) para que
        contraste con "Destinos" justo arriba. Radio 10px y CERO
        box-shadow en toda la sección (antes: rounded-panel/shadow-e3 en la
        foto y la tarjeta flotante, del pase cinematográfico anterior a este
        lote) — se ajustan acá para cumplir la regla dura del encargo; el
        parallax, la holgura del marco y la velocidad NO se tocan.
    --}}
    <section id="about" class="bg-surface">
        <div class="shell-boxed section">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-center lg:gap-16">
                {{-- La foto va a la izquierda en pantallas grandes: ancla el
                     bloque y el texto deja de flotar. --}}
                <div class="order-2 lg:order-1" data-reveal>
                    {{-- Parallax por capas (pase cinematográfico): la foto
                         vive en .parallax-frame, con 14% de holgura vertical
                         (ver app.css), y JS la traslada a una fracción de la
                         velocidad del scroll mientras la tarjeta flotante de
                         abajo (parte del CONTENIDO, no del fondo) se mueve a
                         velocidad normal. Se apaga solo bajo 1024px, con
                         puntero "coarse" o con reduced-motion — ver
                         setupParallax() en app.js. --}}
                    <div class="photo aspect-[4/3] rounded-tile">
                        {{-- Velocidad subida de 0.15 a 0.26 (2026-09-18): a 0.15 el
                             desplazamiento medido con playwright-core era real pero
                             quedaba por debajo del umbral de percepción humana dentro
                             de una sola pasada de scroll (~44px en la ventana visible
                             de la sección). Con 0.26 el mismo tramo de scroll produce
                             ~80px, visible contra la tarjeta flotante fija. El clamp
                             (12% del alto del marco, ver app.css) no se toca: sigue
                             siendo el tope de seguridad frente al 14% de holgura. --}}
                        <div class="parallax-frame" data-parallax="0.26">
                            <x-ui.picture
                                src="{{ asset('images/site/nosotros-viajeros-ruta.jpg') }}"
                                :alt="__('site.home.why_us.photo_alt')"
                                sizes="(min-width: 1024px) 30rem, 92vw"
                                position="center 40%"
                            />
                        </div>
                    </div>
                    {{-- Tarjeta flotante: hermana con margen negativo y
                         z-index propio, no absolute dentro de un contenedor
                         con overflow oculto — así no se recorta ni empuja el
                         alto de la sección. --}}
                    <div class="relative z-10 -mt-10 ml-4 mr-8 flex items-center gap-3 rounded-tile border border-line bg-surface p-4 sm:-mt-12 sm:ml-8 sm:mr-16">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-action" aria-hidden="true">
                            {!! $headsetIcon !!}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ __('site.home.why_us.assistance_title') }}</p>
                            <p class="text-xs leading-relaxed text-text-2">{{ __('site.home.why_us.assistance_description') }}</p>
                        </div>
                    </div>
                </div>

                <div class="order-1 lg:order-2">
                    <x-ui.eyebrow data-reveal class="mb-3">{{ __('site.home.why_us.eyebrow') }}</x-ui.eyebrow>
                    <x-ui.section-title as="h2" data-reveal style="--reveal-delay:60ms">
                        {{ __('site.home.why_us.title_before') }}
                        <span class="text-brand-text">{{ __('site.home.why_us.title_highlight') }}</span>{{ __('site.home.why_us.title_after') }}
                    </x-ui.section-title>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach(__('site.home.why_us.features') as $i => $feature)
                            <x-ui.feature-card
                                :icon="$whyUsIcons[$i] ?? $whyUsIcons[0]"
                                :title="$feature['title']"
                                :description="$feature['description']"
                                data-reveal
                                style="--reveal-delay:{{ min($i, 5) * 80 }}ms"
                            />
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{--
        ============ 5. ACTIVIDADES — tiles Roavio (antes "Experiencias únicas") ============
        Port Roavio (lote 1, 2026-09-21): en Roavio esta sección es una fila
        de 5 tiles de actividad (foto + ícono + rótulo). MISMO DATO que
        antes ($experiences, sin query nueva) — solo cambia la presentación,
        de tarjeta con borde/sombra (x-ui.experience-card, que sigue viva en
        /experiencias sin tocar) a tile plano a sangre completa
        (x-ui.mosaic-tile: radio 10px, scrim-card, CERO box-shadow — el
        mismo componente del mosaico bajo el hero, reutilizado a propósito
        para que "Actividades" se lea como eco del mosaico).

        Contenedor .shell-bleed (1432, a sangre) — contraste fuerte contra
        el "about" boxed de arriba. Banda deliberadamente BAJA (tiles
        aspect-video, no aspect-[3/4]) para que la sección sea corta frente
        a "Tours"/"About": es la pieza que rompe la monotonía de alturas
        (regla del encargo).
    --}}
    <section id="actividades" class="bg-surface pb-2 pt-2">
        <div class="shell-bleed section-tight">
            <div class="px-1">
                <x-ui.eyebrow data-reveal class="mb-3">{{ __('site.home.activities.eyebrow') }}</x-ui.eyebrow>
                <x-ui.section-title as="h2" data-reveal style="--reveal-delay:60ms">
                    <x-slot:action>
                        <x-ui.button variant="link" href="{{ Route::has('experiences.index') ? route('experiences.index') : '#' }}">
                            {{ __('site.home.activities.cta') }} &rarr;
                        </x-ui.button>
                    </x-slot:action>
                    {{ __('site.home.activities.title') }}
                </x-ui.section-title>
            </div>

            @if($experiences->isNotEmpty())
                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
                    @foreach($experiences as $i => $experience)
                        <x-ui.mosaic-tile
                            class="aspect-video"
                            :image="$experience->coverImageUrl() ?? PlaceholderImage::svg(480, 360, $experience->name, $photoPalette[$i % count($photoPalette)])"
                            :image-alt="filled($experience->cover_image_alt) ? $experience->cover_image_alt : $experience->name"
                            :title="$experience->name"
                            :subtitle="\Illuminate\Support\Str::limit($experience->description, 60)"
                            :icon="$experienceIcons[$experience->slug] ?? $defaultExperienceIcon"
                            :href="route('experiences.show', $experience->slug)"
                            sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 90vw"
                            data-reveal
                            style="--reveal-delay:{{ min($i, 5) * 80 }}ms"
                        />
                    @endforeach
                </div>
            @else
                <x-ui.empty-state data-reveal class="mx-1">{{ __('site.home.empty.experiences') }}</x-ui.empty-state>
            @endif
        </div>
    </section>

    {{--
        ============ 6. LO QUE DICEN NUESTROS VIAJEROS (testimonios Roavio) ============
        Decisión anterior (B2): esta sección NO se renderizaba — `reviews` no
        tiene migración y los 3 testimonios del mockup eran reseñas falsas
        (caras de IA, nombres inventados). El encargo de este lote la anula
        explícitamente: "Testimonios NO tienen modelo — maquétalos con
        contenido estático quemado en la vista, con comentario DEMO en cada
        uno" (ver el array $testimonials armado arriba, de
        lang/es/site.php#testimonials.items — cada fila queda marcada
        "DEMO: pendiente modelo" en el comentario del propio lang file).

        El problema original de "caras de IA" NO vuelve: nombre + inicial de
        apellido (no una identidad completa inventada) y CERO foto de
        banco/IA — el avatar es la inicial sobre un cuadrado de color
        (x-ui.testimonial-card variant="tile", 100x101, radio 0, el spec
        exacto del encargo).

        Roavio usa un Swiper de 6; acá, x-ui.carousel-shell (el mismo
        carrusel real — scroll-snap + flechas + puntos — que ya usaba esta
        página para tours, sin sumar una librería nueva). Contenedor
        .shell-narrow (1290, el tercer ancho Roavio, sin consumidor hasta
        este lote — ver tokens.css) para no repetir el .shell-bleed de
        Actividades justo arriba.
    --}}
    @if($testimonialsEnabled)
    <section id="testimonios" class="bg-sand weave">
        <div class="shell-narrow section">
            <x-ui.eyebrow data-reveal class="mb-3">{{ __('site.home.testimonials.eyebrow') }}</x-ui.eyebrow>
            <x-ui.section-title as="h2" data-reveal style="--reveal-delay:60ms">
                {{ __('site.home.testimonials.title') }}
            </x-ui.section-title>

            <x-ui.carousel-shell label="{{ __('site.home.testimonials.title') }}">
                @foreach($testimonials as $i => $t)
                    <li class="w-[300px] shrink-0 snap-start sm:w-[360px]" data-reveal style="--reveal-delay:{{ min($i, 5) * 80 }}ms">
                        {{-- DEMO: pendiente modelo de reseñas (ver comentario arriba). --}}
                        <x-ui.testimonial-card
                            variant="tile"
                            class="h-full"
                            :quote="$t['quote']"
                            :name="$t['name']"
                            :origin="$t['origin']"
                            :avatar-tone="$t['tone']"
                        />
                    </li>
                @endforeach
            </x-ui.carousel-shell>
        </div>
    </section>
    @endif

    {{--
        ============ 6b. ALIADOS Y CERTIFICACIONES (marquee Roavio) ============
        Roavio lleva 21 logos; acá van aliados + los sellos oficiales
        peruanos (RNAVT/MINCETUR — ver x-ui.mincetur-badge, que ya existe y
        no imprime nada sin Setting::get('rnavt_number')). NINGÚN archivo
        real en el proyecto todavía: ni un logo de aliado ni el sello oficial
        (búsqueda en public/images/, sin resultados). Regla dura del
        encargo: "si el sello no está, deja el hueco maquetado, nunca un
        placeholder con aspecto de sello oficial" — por eso CADA tile, aliado
        o sello, es el mismo wordmark de texto neutro (nada que se pueda
        confundir con una certificación real). Pendiente anotado acá y en el
        informe de cierre, no como copy visible en la página (mismo criterio
        que el resto del sitio: nunca lenguaje de obra de cara al visitante).

        Bucle CSS puro (.marquee-track, ver app.css): el array se recorre
        DOS VECES seguidas para que el tramo duplicado entre justo cuando
        sale el primero — nunca Swiper/JS nuevo. Contenedor .shell-bleed
        (1432, a sangre) — la ficha de "logos en fila" pide ancho completo,
        y contrasta con el .shell-narrow de Testimonios justo arriba.
    --}}
    <section id="aliados" aria-labelledby="aliados-title" class="border-y border-line-soft bg-surface py-10 sm:py-12">
        <div class="shell-bleed">
            <p id="aliados-title" class="eyebrow mb-6 text-center text-text-muted">{{ __('site.home.partners.eyebrow') }}</p>

            {{-- aria-hidden: contenido decorativo (wordmarks placeholder,
                 sin enlaces) duplicado para el bucle CSS — sin esto un
                 lector de pantalla lo anunciaría dos veces seguidas. --}}
            <div class="marquee-viewport overflow-hidden" data-reveal aria-hidden="true">
                <div class="marquee-track">
                    @foreach([...$partners, ...$partners] as $partner)
                        <div
                            @class([
                                'mx-3 flex h-14 w-40 shrink-0 items-center justify-center rounded-tile border px-4 text-center text-xs font-semibold uppercase tracking-wide text-text-muted',
                                'border-dashed border-line' => true,
                            ])
                            title="{{ ($partner['seal'] ?? false) ? 'Sello oficial — pendiente de archivo real' : 'Aliado — pendiente de logo real' }}"
                        >
                            {{ $partner['label'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============ 7. NEWSLETTER ============ --}}
    <section class="relative isolate overflow-hidden bg-ink-surface">
        <div class="photo scrim-band absolute inset-0">
            {{-- Mismo tratamiento de parallax que la foto de "por qué
                 elegir viajar con nosotros" (ver comentario ahí): capa de
                 fondo a fracción de la velocidad del scroll, apagada bajo
                 1024px / puntero coarse / reduced-motion. Velocidad subida
                 de 0.12 a 0.22 (2026-09-18) por el mismo motivo: amplitud
                 imperceptible dentro de la ventana real de scroll. --}}
            <div class="parallax-frame" data-parallax="0.22">
                <x-ui.picture
                    src="{{ asset('images/site/hero-valle-sagrado-panoramica.jpg') }}"
                    :alt="__('site.home.newsletter.photo_alt')"
                    sizes="100vw"
                    position="center 55%"
                />
            </div>
        </div>

        <div class="shell section-tight relative z-10">
            <div class="mx-auto max-w-2xl text-center" data-reveal>
                <h2 class="font-display text-h2 font-semibold text-white">{{ __('site.home.newsletter.title') }}</h2>
                <p class="mt-3 text-on-dark-2">{{ __('site.home.newsletter.description') }}</p>

                {{--
                    B4: sin entidad ni endpoint de newsletter (no hay tabla,
                    no hay contrato de datos). Campo y botón DESHABILITADOS y
                    declarados con title/aria-label — mismo patrón que el
                    buscador del header — nunca un formulario que finge
                    funcionar.
                --}}
                <div class="mx-auto mt-7 flex max-w-lg flex-col gap-3 sm:flex-row">
                    <label for="newsletter-email" class="sr-only">{{ __('site.home.newsletter.email_label') }}</label>
                    <input
                        id="newsletter-email"
                        type="email"
                        placeholder="{{ __('site.home.newsletter.email_placeholder') }}"
                        disabled
                        title="{{ __('site.home.newsletter.unavailable') }}"
                        class="w-full rounded-full border border-white/30 bg-white/10 px-5 py-3 text-sm text-white placeholder:text-on-dark-3 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                    <x-ui.button
                        disabled
                        title="{{ __('site.home.newsletter.unavailable') }}"
                        aria-label="{{ __('site.home.newsletter.unavailable') }}"
                        class="shrink-0 px-6 py-3"
                    >
                        {{ __('site.home.newsletter.submit') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </section>

</x-layout>
