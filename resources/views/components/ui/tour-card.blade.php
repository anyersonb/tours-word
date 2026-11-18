@props([
    'image',
    'imageAlt' => '',
    'badge' => null,
    'badgeVariant' => 'green',
    'title',
    'summary' => null,
    'duration' => null,
    'category' => null,
    'penCents' => 0,
    'usdCents' => 0,
    'href' => '#',
    'location' => null,
    // Cuánto ocupa la tarjeta EN PANTALLA. Lo decide quien la coloca (la
    // rejilla de un índice no mide lo mismo que un slide del carrusel), y
    // x-ui.picture lo necesita para no bajarse la variante grande (sin esto
    // el navegador asume 100vw y se baja la mas pesada).
    'sizes' => '(min-width: 1024px) 22rem, (min-width: 640px) 45vw, 90vw',
    // Nivel del encabezado del título en el esquema del documento. Lo decide
    // quien coloca la tarjeta, igual que "sizes": en un índice la tarjeta
    // cuelga directamente del H1 de la pantalla (nivel 2); dentro de una
    // sección que ya tiene su propio H2 (home, fichas) cuelga de esa sección
    // (nivel 3, el de por defecto). El nivel NO decide el tamaño de letra:
    // ese sale siempre de text-h3, así que la tarjeta se ve igual en las dos.
    'headingLevel' => 3,
    // Prop aditiva (lote 1, port Roavio): 'card' es el look de siempre
    // (radius-card, shadow-e1/e3) que usan tours/index y demás listados —
    // NO SE TOCA. 'flat' es el tratamiento Roavio de la rejilla de la home
    // (radius-tile, cero box-shadow en cualquier sitio): solo lo pide quien
    // coloca la tarjeta, nunca cambia el valor por defecto.
    'variant' => 'card',
    // Aditiva (mockups móvil, lote 16): sale del dato real is_featured del
    // modelo. Solo agrega el badge "Destacado" — nunca "Más reservado" (esa
    // insignia del mockup es una afirmación sin dato que la respalde, ver
    // informe de cierre).
    'isFeatured' => false,
    // Aditiva (mockups móvil, lote 16): activa el tratamiento de la tarjeta
    // del mockup de tour ("Full day a Ica...") — badges arriba de la foto en
    // vez de la fila duration/category, y la grilla 2x2 de atributos DEMO en
    // vez de esa misma fila. Default false: tours/index y el styleguide (los
    // únicos otros llamadores, ver grep) no cambian ni un píxel.
    'showAttributes' => false,
    // Aditivas (lote 19, maqueta del cliente): los tres bloques se pintan
    // SOLO si llega el dato; ninguna cifra vive acá. Hoy Tour no tiene estos
    // campos, así que en la home real no se ven.
    // rating: nota 0-5 · reviewsCount: nº de reseñas · compareAtPrice: precio
    // anterior como ['pen' => centimos, 'usd' => centimos] (el % se
    // calcula acá, no se recibe) · lowAvailability: bool "Últimos cupos".
    'rating' => null,
    'reviewsCount' => null,
    'compareAtPrice' => null,
    'lowAvailability' => false,
])
@php
    $hl = max(2, min(4, (int) $headingLevel));
    $surfaceClasses = $variant === 'flat'
        ? 'rounded-tile border border-line bg-surface transition-transform duration-300 hover:-translate-y-1 focus-within:-translate-y-1'
        : 'rounded-card border border-line bg-surface shadow-e1 transition-[box-shadow,transform] duration-300 hover:-translate-y-1 hover:shadow-e3 focus-within:-translate-y-1 focus-within:shadow-e3';

    // DEMO: pendiente campos en Tour (recojo, idiomas, cancelación, frecuencia
    // de salida no existen como columnas todavía — ver encargo del lote 16).
    // UN SOLO array acá para que backend lo reemplace en un único punto el
    // día que esos campos existan de verdad.
    $iconTruck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M2 8h11v8H2zM13 11h4l4 3v2h-8z"/><circle cx="6.5" cy="18" r="1.6"/><circle cx="16.5" cy="18" r="1.6"/></svg>';
    $iconGlobe = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>';
    $iconShieldCheck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>';
    $demoAttributes = [
        ['icon' => $iconTruck, 'label' => __('site.ui.tour_card.attr_pickup')],
        ['icon' => $iconGlobe, 'label' => __('site.ui.tour_card.attr_languages')],
        ['icon' => $iconShieldCheck, 'label' => __('site.ui.tour_card.attr_cancellation')],
        ['icon' => $iconCalendar, 'label' => __('site.ui.tour_card.attr_daily')],
    ];
    // Maqueta 3 (lote 20): la duración real va como PRIMER atributo (reloj),
    // no como pastilla sobre la foto. Con duración se descarta "Salida
    // diaria" (DEMO) para seguir en 2x2; sin ella queda la grilla de antes.
    $attributes2x2 = filled($duration)
        ? array_merge([['icon' => $iconClock, 'label' => $duration]], array_slice($demoAttributes, 0, 3))
        : $demoAttributes;

    // Badges de foto: solo con showAttributes (mockup de tour), y solo con
    // dato real. "Destacado" viene de is_featured; la duración es la MISMA
    // que ya llega en $duration (duration_label real) — nunca un texto
    // "Full day" cableado, se imprime lo que traiga el tour.
    $photoBadges = [];
    if ($showAttributes) {
        if ($isFeatured) {
            $photoBadges[] = ['label' => __('site.ui.tour_card.badge_featured'), 'variant' => 'white'];
        }
    }

    $hasReviews = is_numeric($rating) && is_numeric($reviewsCount) && (int) $reviewsCount > 0;
    $ratingValue = $hasReviews ? max(0, min(5, (float) $rating)) : 0;

    // Descuento: solo si el precio anterior es MAYOR que el actual. El % sale
    // del cociente (PEN si hay, si no USD), nunca de una prop.
    $oldPen = (int) (is_array($compareAtPrice) ? ($compareAtPrice['pen'] ?? 0) : 0);
    $oldUsd = (int) (is_array($compareAtPrice) ? ($compareAtPrice['usd'] ?? 0) : 0);
    $discountPercent = 0;
    if ($oldPen > 0 && (int) $penCents > 0 && $oldPen > (int) $penCents) {
        $discountPercent = (int) round((1 - ((int) $penCents / $oldPen)) * 100);
    } elseif ($oldPen <= 0 && $oldUsd > 0 && (int) $usdCents > 0 && $oldUsd > (int) $usdCents) {
        $discountPercent = (int) round((1 - ((int) $usdCents / $oldUsd)) * 100);
    }
    $hasDiscount = $discountPercent > 0;
@endphp
{{-- Pendiente backend: campos rating/reviews/compare_at_price/cupos en Tour --}}
{{--
    Tarjeta de tour. Toda la superficie es enlace (::before del <a> cubre la
    tarjeta) para que el objetivo táctil sea la tarjeta entera, no solo el
    botón; el botón se mantiene visible porque es la afordancia que la gente
    busca, y queda por encima con su propio z-index.
--}}
<article {{ $attributes->class(['group relative flex h-full flex-col overflow-hidden', $surfaceClasses]) }}>
    <div class="photo photo-zoom aspect-[4/3] w-full">
        <x-ui.picture :src="$image" :alt="$imageAlt" :sizes="$sizes" />
        @if(!empty($photoBadges) || $lowAvailability)
            <div class="absolute left-3 top-3 z-[2] flex flex-col items-start gap-1.5">
                @if(!empty($photoBadges))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($photoBadges as $b)
                            <x-ui.badge :variant="$b['variant']" size="lg" :dot="true">{{ $b['label'] }}</x-ui.badge>
                        @endforeach
                    </div>
                @elseif($badge)
                    <x-ui.badge :variant="$badgeVariant">{{ $badge }}</x-ui.badge>
                @endif
                @if($lowAvailability)
                    <x-ui.badge variant="gold" :size="$showAttributes ? 'lg' : 'sm'" :dot="$showAttributes">{{ __('site.ui.tour_card.low_availability') }}</x-ui.badge>
                @endif
            </div>
        @elseif($badge)
            <x-ui.badge :variant="$badgeVariant" class="absolute left-3 top-3 z-[2]">{{ $badge }}</x-ui.badge>
        @endif
        @if($location)
            <span class="absolute bottom-3 left-3 z-[2] inline-flex items-center gap-1 rounded-full bg-ink-surface/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur-sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                {{ $location }}
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-3 {{ $showAttributes ? 'p-4' : 'p-5' }}">
        {{-- Maqueta 3: título en MAYÚSCULAS serif solo en la variante de la
             maqueta (showAttributes). Es CSS: el dato no se toca. --}}
        <h{{ $hl }} class="font-display {{ $showAttributes ? 'text-lg font-semibold uppercase leading-tight' : 'text-h3 font-semibold' }} text-ink">
            <a href="{{ $href }}" class="before:absolute before:inset-0 before:content-['']">
                {{ $title }}
            </a>
        </h{{ $hl }}>

        @if($hasReviews)
            <div class="flex items-center gap-1.5 text-sm text-text-2">
                <span class="flex items-center gap-0.5" aria-hidden="true">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="{{ $showAttributes ? 'h-[18px] w-[18px]' : 'h-4 w-4' }} {{ $i <= round($ratingValue) ? 'text-amber' : 'text-line' }}" viewBox="0 0 24 24" fill="currentColor"><path d="m12 2.5 2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96L12 2.5Z"/></svg>
                    @endfor
                </span>
                <span class="font-semibold text-ink"><span class="sr-only">{{ __('site.ui.tour_card.rating_label', ['rating' => number_format($ratingValue, 1, '.', '')]) }} </span>{{ number_format($ratingValue, 1, '.', '') }}</span>
                <span aria-hidden="true">&middot;</span>
                <span class="{{ $showAttributes ? 'capitalize' : '' }}">{{ trans_choice('site.ui.tour_card.reviews', (int) $reviewsCount, ['count' => (int) $reviewsCount]) }}</span>
            </div>
        @endif

        {{-- La maqueta 3 no lleva resumen: en la variante de la maqueta
             (showAttributes) no se pinta. El "Resumen" que se veía en el
             lote 19 era el TEXTO de prueba pasado en $summary, no un rótulo
             del componente: el resto de variantes sigue mostrándolo. --}}
        @if($summary && !$showAttributes)
            <p class="line-clamp-2 text-sm leading-relaxed text-text-2">{{ $summary }}</p>
        @endif

        @if(($duration || $category) && !$showAttributes)
            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-text-muted">
                @if($duration)
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" /></svg>
                        {{ $duration }}
                    </span>
                @endif
                @if($category)
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 21V8l8-5 8 5v13" /><path d="M9 21v-6h6v6" /></svg>
                        {{ $category }}
                    </span>
                @endif
            </div>
        @endif

        @if($showAttributes)
            {{-- DEMO: pendiente campos en Tour (ver $demoAttributes arriba). --}}
            {{-- Maqueta 3: cada atributo es una caja arena con radio, círculo
                 blanco con el ícono a la izquierda y texto en negrita. --}}
            <div class="@container grid grid-cols-2 gap-2 text-ink">
                @foreach($attributes2x2 as $attr)
                    <div class="flex min-w-0 items-center gap-1 rounded-lg bg-sand p-1 @max-[12rem]:flex-col @max-[12rem]:items-start @max-[12rem]:p-1.5 text-[10.5px] font-semibold leading-tight @[16rem]:p-2 @[16rem]:text-xs">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-surface text-ink @[16rem]:h-7 @[16rem]:w-7 [&_svg]:h-3 [&_svg]:w-3 @[16rem]:[&_svg]:h-4 @[16rem]:[&_svg]:w-4" aria-hidden="true">{!! $attr['icon'] !!}</span>
                        <span class="min-w-0">{{ $attr['label'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if($hasDiscount && !$showAttributes)
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-danger/30 px-2.5 py-1 font-semibold text-danger">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="7.5" cy="8.5" r="1.2"/></svg>
                    {{ __('site.ui.tour_card.discount', ['percent' => $discountPercent]) }}
                </span>
                <span class="inline-flex items-baseline gap-1 text-text-muted">
                    <span class="font-semibold uppercase tracking-wide">{{ __('site.ui.tour_card.before') }}</span>
                    <x-ui.money :pen-cents="$oldPen ?: $penCents" :usd-cents="$oldUsd ?: $usdCents" class="line-through" />
                </span>
            </div>
        @endif

        @if($showAttributes)
            {{-- Maqueta 3: un solo panel arena que agrupa descuento, ANTES,
                 AHORA, precio y el botón oscuro con la flecha
                 en círculo naranja (token --gold; flecha espresso 5.72:1 sobre el dorado). Cada fila solo existe si hay dato. --}}
            <div class="mt-auto flex flex-col gap-2 rounded-xl bg-sand p-3">
                @if($hasDiscount)
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 rounded-md border border-danger/30 bg-danger/5 px-2 py-1 font-semibold text-danger">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="7.5" cy="8.5" r="1.4" fill="#fff"/></svg>
                            {{ __('site.ui.tour_card.discount', ['percent' => $discountPercent]) }}
                        </span>
                        <span class="inline-flex items-baseline gap-1 text-text-muted">
                            <span class="font-medium uppercase tracking-wide">{{ __('site.ui.tour_card.before') }}</span>
                            <x-ui.money :pen-cents="$oldPen ?: $penCents" :usd-cents="$oldUsd ?: $usdCents" class="line-through" />
                        </span>
                    </div>
                @endif
                <div class="flex flex-wrap items-end justify-between gap-x-3 gap-y-2">
                    <div class="flex min-w-0 flex-col">
                        @if($hasDiscount)
                            <span class="text-xs font-bold uppercase tracking-wide text-accent-green">{{ __('site.ui.tour_card.now') }}</span>
                        @endif
                        <x-ui.money :pen-cents="$penCents" :usd-cents="$usdCents" :prefix="$hasDiscount ? null : __('site.ui.tour_card.price_prefix')" class="font-display text-2xl font-semibold leading-tight text-ink" />
                    </div>
                    <span class="relative z-[1] ml-auto inline-flex items-center justify-center gap-3 rounded-full bg-ink-surface py-1.5 pl-4 pr-1.5 text-sm font-semibold text-on-dark transition-colors group-hover:bg-ink-surface-2">
                        {{ __('site.ui.tour_card.cta') }}
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold text-ink" aria-hidden="true">
                            <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </span>
                    </span>
                </div>
            </div>
        @else
        <div class="mt-auto flex flex-wrap items-end justify-between gap-3 border-t border-line-soft pt-4">
            <div class="flex flex-col">
                @if($hasDiscount)
                    <span class="text-xs font-semibold uppercase tracking-wide text-accent-green">{{ __('site.ui.tour_card.now') }}</span>
                @endif
                <x-ui.money :pen-cents="$penCents" :usd-cents="$usdCents" :prefix="$hasDiscount ? null : __('site.ui.tour_card.price_prefix')" class="font-display text-h3 font-semibold text-ink" />
            </div>
            <span class="relative z-[1] inline-flex items-center justify-center gap-2 rounded-full bg-action px-4 py-2 text-sm font-medium text-on-action transition-colors group-hover:bg-action-hover">
                {{ __('site.ui.tour_card.cta') }}
                <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
        </div>
        @endif
    </div>
</article>
