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
])
@php
    $hl = max(2, min(4, (int) $headingLevel));
    $surfaceClasses = $variant === 'flat'
        ? 'rounded-tile border border-line bg-surface transition-transform duration-300 hover:-translate-y-1 focus-within:-translate-y-1'
        : 'rounded-card border border-line bg-surface shadow-e1 transition-[box-shadow,transform] duration-300 hover:-translate-y-1 hover:shadow-e3 focus-within:-translate-y-1 focus-within:shadow-e3';
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
        @if($badge)
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

        @if($duration || $category)
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

        <div class="mt-auto flex flex-wrap items-end justify-between gap-3 border-t border-line-soft pt-4">
            <x-ui.money :pen-cents="$penCents" :usd-cents="$usdCents" :prefix="__('site.ui.tour_card.price_prefix')" class="font-display text-h3 font-semibold text-ink" />
            <span class="relative z-[1] inline-flex items-center justify-center gap-2 rounded-full bg-action px-4 py-2 text-sm font-medium text-on-action transition-colors group-hover:bg-action-hover">
                {{ __('site.ui.tour_card.cta') }}
                <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
        </div>
    </div>
</article>
