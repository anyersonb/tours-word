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
     */
    $heroSlides = [
        ['file' => 'hero-machupicchu-amanecer', 'position' => 'center 42%'],
        ['file' => 'hero-valle-sagrado-panoramica', 'position' => 'center 60%'],
        ['file' => 'hero-cordillera-rio', 'position' => 'center 46%'],
    ];
    // ['alt' => ..., 'label' => ...] por diapositiva. "alt" queda sin usar en
    // el <img> (las tres fotos son decorativas: el titular ya dice de qué
    // trata la página, y cada foto ya se describe en el anunciador aria-live
    // y en el aria-label de su botón indicador — un alt real duplicaría esa
    // lectura). Se deja declarado para que la pasada B lo reutilice donde la
    // foto SÍ sea contenido con significado propio.
    $heroSlideLabels = __('site.home.hero.slides');
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
        {{-- min-h-[100svh] menos el alto del header (h-16 = 4rem, sticky:
             ocupa su propio espacio en el flujo, no se superpone al hero) —
             así el conjunto header+hero llena la pantalla completa en la
             primera vista, que es el punto de "full-screen" del encargo. --}}
        class="relative isolate flex min-h-[calc(100svh-4rem)] flex-col overflow-hidden bg-ink-surface"
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
                 se anula solo con prefers-reduced-motion (ver app.css). --}}
            <div class="hidden justify-center pb-5 sm:flex">
                <a
                    href="#tours-destacados"
                    class="hero-scroll-cue inline-flex flex-col items-center gap-1 rounded-full px-2 py-1 text-white/80 transition-colors hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                >
                    <span class="text-xs font-medium uppercase tracking-wide">{{ __('site.home.hero.scroll_cue') }}</span>
                    <svg class="hero-scroll-cue__icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ============ 2. TOURS DESTACADOS ============ --}}
    <section id="tours-destacados" class="bg-surface">
        <div class="shell section">
            <x-ui.eyebrow data-reveal class="mb-3">{{ __('site.home.featured_tours.eyebrow') }}</x-ui.eyebrow>
            <x-ui.section-title as="h2" data-reveal style="--reveal-delay:60ms">
                <x-slot:action>
                    <x-ui.button variant="link" href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}">
                        {{ __('site.home.featured_tours.cta') }} &rarr;
                    </x-ui.button>
                </x-slot:action>
                {{ __('site.home.featured_tours.title') }}
            </x-ui.section-title>

            @if($featuredTours->count() >= 4)
                {{-- Carrusel solo a partir de 4: con 2 o 3 tarjetas no hay
                     nada que desplazar y el propio componente esconde sus
                     controles, así que la sección se veía como una rejilla
                     torcida. --}}
                <x-ui.carousel-shell label="{{ __('site.home.featured_tours.title') }}">
                    @foreach($featuredTours as $tour)
                        <li class="w-[280px] shrink-0 snap-start sm:w-[340px]" data-reveal style="--reveal-delay:{{ min($loop->index, 5) * 80 }}ms">
                            <x-ui.tour-card
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
                                sizes="340px"
                            />
                        </li>
                    @endforeach
                </x-ui.carousel-shell>
            @elseif($featuredTours->isNotEmpty())
                {{-- Rejilla que se centra sola: con un único tour destacado
                     (el caso de hoy) la tarjeta queda centrada, no huérfana
                     pegada al margen izquierdo como estaba. --}}
                <div class="mx-auto grid max-w-6xl justify-center gap-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),24rem))]">
                    @foreach($featuredTours as $tour)
                        <x-ui.tour-card
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
                            sizes="(min-width: 640px) 24rem, 90vw"
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
    <section class="weave bg-sand">
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

    {{-- ============ 4. ¿POR QUÉ ELEGIR VIAJAR CON NOSOTROS? ============ --}}
    <section class="bg-surface">
        <div class="shell section">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
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
                    <div class="photo aspect-[4/3] rounded-panel shadow-e3">
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
                                sizes="(min-width: 1024px) 36rem, 92vw"
                                position="center 40%"
                            />
                        </div>
                    </div>
                    {{-- Tarjeta flotante: hermana con margen negativo y
                         z-index propio, no absolute dentro de un contenedor
                         con overflow oculto — así no se recorta ni empuja el
                         alto de la sección. --}}
                    <div class="relative z-10 -mt-10 ml-4 mr-8 flex items-center gap-3 rounded-card border border-line bg-surface p-4 shadow-e3 sm:-mt-12 sm:ml-8 sm:mr-16">
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

    {{-- ============ 5. EXPERIENCIAS ÚNICAS ============ --}}
    <section class="weave bg-sand">
        <div class="shell section">
            <x-ui.eyebrow data-reveal class="mb-3">{{ __('site.home.experiences.eyebrow') }}</x-ui.eyebrow>
            <x-ui.section-title as="h2" data-reveal style="--reveal-delay:60ms">
                <x-slot:action>
                    <x-ui.button variant="link" href="{{ Route::has('experiences.index') ? route('experiences.index') : '#' }}">
                        {{ __('site.home.experiences.cta') }} &rarr;
                    </x-ui.button>
                </x-slot:action>
                {{ __('site.home.experiences.title') }}
            </x-ui.section-title>

            @if($experiences->isNotEmpty())
                <div class="grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
                    @foreach($experiences as $i => $experience)
                        {{-- D · lote 1/etapa D: mismo patrón que Destinos. --}}
                        <x-ui.experience-card
                            :image="$experience->coverImageUrl() ?? PlaceholderImage::svg(480, 360, $experience->name, $photoPalette[$i % count($photoPalette)])"
                            :image-alt="filled($experience->cover_image_alt) ? $experience->cover_image_alt : $experience->name"
                            :title="$experience->name"
                            :description="$experience->description"
                            :icon="$experienceIcons[$experience->slug] ?? $defaultExperienceIcon"
                            href="{{ route('experiences.show', $experience->slug) }}"
                            data-reveal
                            style="--reveal-delay:{{ min($i, 5) * 80 }}ms"
                        />
                    @endforeach
                </div>
            @else
                <x-ui.empty-state data-reveal>{{ __('site.home.empty.experiences') }}</x-ui.empty-state>
            @endif
        </div>
    </section>

    {{--
        ============ 6. LO QUE DICEN NUESTROS VIAJEROS ============
        B2: NO se renderiza en la Home. `reviews` no tiene migración (lote
        4/5) y los 3 testimonios del mockup son reseñas falsas (caras de IA,
        nombres inventados). El componente x-ui.testimonial-card existe y se
        ve en /_styleguide con :sample="true", pero ninguna página real lo
        instancia todavía — decisión tomada acá, en la página, no en el
        componente (así lo documenta 00-sistema-diseno.md).
    --}}

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
