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
@endphp
<x-layout title="{{ __('site.home.meta.title') }}" description="{{ __('site.home.meta.description') }}">

    {{-- ============ 1. HERO ============ --}}
    <section class="overflow-hidden bg-surface">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-12 lg:px-8 lg:py-20">
            <div>
                {{-- B3: no imprime nada sin Setting::get('rnavt_number') --}}
                <x-ui.mincetur-badge class="mb-5" />

                <h1 class="font-display text-3xl font-semibold leading-[1.15] text-ink sm:text-4xl lg:text-5xl">
                    {{ __('site.home.hero.title_before') }}
                    <span class="text-brand-text">{{ __('site.home.hero.title_highlight') }}</span>
                    {{ __('site.home.hero.title_after') }}
                </h1>

                <p class="mt-4 max-w-xl text-base text-text-2 sm:text-lg">
                    {{ __('site.home.hero.subtitle') }}
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-4">
                    <x-ui.button href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}">
                        {{ __('site.home.hero.cta_primary') }}
                    </x-ui.button>
                    <x-ui.button variant="ghost" href="{{ Route::has('destinations.index') ? route('destinations.index') : '#' }}" :icon="$playIcon">
                        {{ __('site.home.hero.cta_secondary') }}
                    </x-ui.button>
                </div>

                <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3">
                    @foreach($heroTrustIcons as $key => $icon)
                        <x-ui.trust-badge :icon="$icon">{{ __('site.home.hero.trust.'.$key) }}</x-ui.trust-badge>
                    @endforeach
                </div>
            </div>

            <div class="relative">
                <div class="aspect-[4/5] w-full overflow-hidden rounded-3xl bg-surface-2 sm:aspect-[16/10] lg:aspect-[4/5]">
                    <img
                        src="{{ PlaceholderImage::svg(1000, 1250, 'Foto del hero (pendiente)', '3d4a42') }}"
                        alt="{{ __('site.home.hero.photo_alt') }}"
                        width="1000" height="1250"
                        class="h-full w-full object-cover"
                    >
                </div>

                {{-- B1: x-ui.stats-strip no imprime nada sin Setting stat_*.
                     Este wrapper no tiene fondo/borde propio, así que si el
                     hijo no renderiza nada, tampoco queda ninguna caja vacía. --}}
                <div class="absolute -bottom-6 left-4 right-4 sm:left-auto sm:right-6 sm:w-[min(90%,26rem)]">
                    <x-ui.stats-strip />
                </div>
            </div>
        </div>
    </section>

    {{-- ============ 2. TOURS DESTACADOS ============ --}}
    @if($featuredTours->isNotEmpty())
        <section class="bg-surface">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <x-ui.section-title as="h2">
                    <x-slot:action>
                        <x-ui.button variant="link" href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}">
                            {{ __('site.home.featured_tours.cta') }} &rarr;
                        </x-ui.button>
                    </x-slot:action>
                    {{ __('site.home.featured_tours.title') }}
                </x-ui.section-title>

                @if($featuredTours->count() >= 2)
                    <x-ui.carousel-shell label="{{ __('site.home.featured_tours.title') }}">
                        @foreach($featuredTours as $i => $tour)
                            <li class="w-[280px] shrink-0 snap-start sm:w-[340px]">
                                <x-ui.tour-card
                                    :image="optional($tour->images->first())->url() ?? PlaceholderImage::svg(480, 360, $tour->title, $photoPalette[$i % count($photoPalette)])"
                                    :image-alt="$tour->title"
                                    :title="$tour->title"
                                    :summary="$tour->summary"
                                    :duration="$tour->duration_label"
                                    :category="optional($tour->experiences->first())->name"
                                    :pen-cents="$tour->price_pen_cents"
                                    :usd-cents="$tour->price_usd_cents"
                                    href="#"
                                />
                            </li>
                        @endforeach
                    </x-ui.carousel-shell>
                @else
                    <div class="max-w-sm">
                        <x-ui.tour-card
                            :image="optional($featuredTours->first()->images->first())->url() ?? PlaceholderImage::svg(480, 360, $featuredTours->first()->title, $photoPalette[0])"
                            :image-alt="$featuredTours->first()->title"
                            :title="$featuredTours->first()->title"
                            :summary="$featuredTours->first()->summary"
                            :duration="$featuredTours->first()->duration_label"
                            :category="optional($featuredTours->first()->experiences->first())->name"
                            :pen-cents="$featuredTours->first()->price_pen_cents"
                            :usd-cents="$featuredTours->first()->price_usd_cents"
                            href="#"
                        />
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ============ 3. DESTINOS IMPERDIBLES ============ --}}
    @if($destinations->isNotEmpty())
        <section class="bg-surface">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <x-ui.section-title as="h2">
                    <x-slot:action>
                        <x-ui.button variant="link" href="{{ Route::has('destinations.index') ? route('destinations.index') : '#' }}">
                            {{ __('site.home.destinations.cta') }} &rarr;
                        </x-ui.button>
                    </x-slot:action>
                    {{ __('site.home.destinations.title') }}
                </x-ui.section-title>

                {{-- B5: rejilla adaptable (auto-fit), no cablea 4 columnas —
                     hoy hay 2 destinos sembrados, no 4 como el mockup. --}}
                <div class="grid justify-center gap-4 [grid-template-columns:repeat(auto-fit,minmax(220px,280px))]">
                    @foreach($destinations as $i => $destination)
                        {{--
                            D · lote 1/etapa D: foto real de catálogo con el
                            placeholder SVG como respaldo (nunca al revés).
                            El alt real viene de cover_image_alt; sin ese
                            dato, cae al nombre del destino — nunca el
                            nombre del archivo ni un alt vacío en una imagen
                            que aporta significado.
                        --}}
                        <x-ui.destination-card
                            :image="$destination->coverImageUrl() ?? PlaceholderImage::svg(480, 600, $destination->name, $photoPalette[$i % count($photoPalette)])"
                            :image-alt="filled($destination->cover_image_alt) ? $destination->cover_image_alt : $destination->name"
                            :name="$destination->name"
                            :tagline="$destination->description"
                            href="#"
                        />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ 4. ¿POR QUÉ ELEGIR VIAJAR CON NOSOTROS? ============ --}}
    {{-- B10: sección explícitamente sobre --ground (fondo pálido), a
         diferencia de las secciones vecinas, que van sobre --surface. --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <x-ui.section-title as="h2">
                {{ __('site.home.why_us.title_before') }}
                <span class="text-brand-text">{{ __('site.home.why_us.title_highlight') }}</span>{{ __('site.home.why_us.title_after') }}
            </x-ui.section-title>

            <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach(__('site.home.why_us.features') as $i => $feature)
                        <x-ui.feature-card
                            :icon="$whyUsIcons[$i] ?? $whyUsIcons[0]"
                            :title="$feature['title']"
                            :description="$feature['description']"
                        />
                    @endforeach
                </div>

                <div class="relative">
                    <div class="aspect-[4/3] overflow-hidden rounded-3xl bg-surface-2">
                        <img
                            src="{{ PlaceholderImage::svg(900, 700, 'Foto pendiente', '2c6fa8') }}"
                            alt="{{ __('site.home.why_us.photo_alt') }}"
                            width="900" height="700" loading="lazy"
                            class="h-full w-full object-cover"
                        >
                    </div>
                    <div class="absolute -bottom-6 left-4 flex max-w-[280px] items-center gap-3 rounded-2xl border border-line bg-surface p-4 shadow-md sm:left-6">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-action" aria-hidden="true">
                            {!! $headsetIcon !!}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ __('site.home.why_us.assistance_title') }}</p>
                            <p class="text-xs text-text-2">{{ __('site.home.why_us.assistance_description') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ 5. EXPERIENCIAS ÚNICAS ============ --}}
    @if($experiences->isNotEmpty())
        <section class="bg-surface">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <x-ui.section-title as="h2">
                    <x-slot:action>
                        <x-ui.button variant="link" href="{{ Route::has('experiences.index') ? route('experiences.index') : '#' }}">
                            {{ __('site.home.experiences.cta') }} &rarr;
                        </x-ui.button>
                    </x-slot:action>
                    {{ __('site.home.experiences.title') }}
                </x-ui.section-title>

                <div class="grid justify-center gap-6 [grid-template-columns:repeat(auto-fit,minmax(200px,260px))]">
                    @foreach($experiences as $i => $experience)
                        {{-- D · lote 1/etapa D: mismo patrón que Destinos arriba. --}}
                        <x-ui.experience-card
                            :image="$experience->coverImageUrl() ?? PlaceholderImage::svg(480, 360, $experience->name, $photoPalette[$i % count($photoPalette)])"
                            :image-alt="filled($experience->cover_image_alt) ? $experience->cover_image_alt : $experience->name"
                            :title="$experience->name"
                            :description="$experience->description"
                            :icon="$experienceIcons[$experience->slug] ?? $defaultExperienceIcon"
                            href="#"
                        />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

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
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="grid overflow-hidden rounded-3xl border border-line bg-brand-50 lg:grid-cols-2">
                <div class="aspect-[16/9] lg:aspect-auto">
                    <img
                        src="{{ PlaceholderImage::svg(700, 600, 'Foto pendiente', '135338') }}"
                        alt="{{ __('site.home.newsletter.photo_alt') }}"
                        width="700" height="600" loading="lazy"
                        class="h-full w-full object-cover"
                    >
                </div>
                <div class="flex flex-col justify-center gap-4 p-8 sm:p-10">
                    <h2 class="font-display text-2xl font-semibold text-ink sm:text-3xl">{{ __('site.home.newsletter.title') }}</h2>
                    <p class="text-text-2">{{ __('site.home.newsletter.description') }}</p>

                    {{--
                        B4: sin entidad ni endpoint de newsletter (no hay
                        tabla, no hay contrato de datos). Campo y botón
                        DESHABILITADOS y declarados con title/aria-label —
                        mismo patrón que el buscador del header — nunca un
                        formulario que finge funcionar.
                    --}}
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <label for="newsletter-email" class="sr-only">{{ __('site.home.newsletter.email_label') }}</label>
                        <input
                            id="newsletter-email"
                            type="email"
                            placeholder="{{ __('site.home.newsletter.email_placeholder') }}"
                            disabled
                            title="{{ __('site.home.newsletter.unavailable') }}"
                            class="w-full rounded-full border border-line bg-surface px-4 py-2.5 text-sm text-ink placeholder:text-text-muted disabled:cursor-not-allowed disabled:opacity-70 sm:max-w-xs"
                        >
                        <x-ui.button
                            disabled
                            title="{{ __('site.home.newsletter.unavailable') }}"
                            aria-label="{{ __('site.home.newsletter.unavailable') }}"
                        >
                            {{ __('site.home.newsletter.submit') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layout>
