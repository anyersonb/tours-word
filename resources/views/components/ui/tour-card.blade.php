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
    $demoAttributes = [
        ['icon' => $iconTruck, 'label' => __('site.ui.tour_card.attr_pickup')],
        ['icon' => $iconGlobe, 'label' => __('site.ui.tour_card.attr_languages')],
        ['icon' => $iconShieldCheck, 'label' => __('site.ui.tour_card.attr_cancellation')],
        ['icon' => $iconCalendar, 'label' => __('site.ui.tour_card.attr_daily')],
    ];

    // Badges de foto: solo con showAttributes (mockup de tour), y solo con
    // dato real. "Destacado" viene de is_featured; la duración es la MISMA
    // que ya llega en $duration (duration_label real) — nunca un texto
    // "Full day" cableado, se imprime lo que traiga el tour.
    $photoBadges = [];
    if ($showAttributes) {
        if ($isFeatured) {
            $photoBadges[] = ['label' => __('site.ui.tour_card.badge_featured'), 'variant' => 'amber'];
        }
        if (filled($duration)) {
            $photoBadges[] = ['label' => $duration, 'variant' => 'white'];
        }
    }
@endphp
{{--
    Tarjeta de tour. Toda la superficie es enlace (::before del <a> cubre la
    tarjeta) para que el objetivo táctil sea la tarjeta entera, no solo el
    botón; el botón se mantiene visible porque es la afordancia que la gente
    busca, y queda por encima con su propio z-index.
--}}
<article {{ $attributes->class(['group relative flex h-full flex-col overflow-hidden', $surfaceClasses]) }}>
    <div class="photo photo-zoom aspect-[4/3] w-full">
        <x-ui.picture :src="$image" :alt="$imageAlt" :sizes="$sizes" />
        @if(!empty($photoBadges))
            <div class="absolute left-3 top-3 z-[2] flex flex-wrap gap-1.5">
                @foreach($photoBadges as $b)
                    <x-ui.badge :variant="$b['variant']">{{ $b['label'] }}</x-ui.badge>
                @endforeach
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

    <div class="flex flex-1 flex-col gap-3 p-5">
        <h{{ $hl }} class="font-display text-h3 font-semibold text-ink">
            <a href="{{ $href }}" class="before:absolute before:inset-0 before:content-['']">
                {{ $title }}
            </a>
        </h{{ $hl }}>

        @if($summary)
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
            <div class="grid grid-cols-2 gap-3 text-xs text-text-2">
                @foreach($demoAttributes as $attr)
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-action" aria-hidden="true">{!! $attr['icon'] !!}</span>
                        <span>{{ $attr['label'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-auto flex flex-wrap items-end justify-between gap-3 border-t border-line-soft pt-4">
            <x-ui.money :pen-cents="$penCents" :usd-cents="$usdCents" :prefix="__('site.ui.tour_card.price_prefix')" class="font-display text-h3 font-semibold text-ink" />
            <span class="relative z-[1] inline-flex items-center justify-center gap-2 rounded-full bg-action px-4 py-2 text-sm font-medium text-on-action transition-colors group-hover:bg-action-hover">
                {{ __('site.ui.tour_card.cta') }}
                <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
        </div>
    </div>
</article>
