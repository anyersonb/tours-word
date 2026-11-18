@props([
    'icon', // slot HTML del ícono (mismo patrón que x-ui.button/icon)
    'title',
    'description' => null,
])
{{--
    Caja de atributo de la sección "¿Por qué elegir viajar con nosotros?"
    (A9, lote 1): ícono + título + descripción corta, sin foto.
    Copy de marketing genérico (viene de lang/es/site.php), no dato de CMS.

    Port Roavio (lote 1, 2026-09-21): único consumidor de este componente es
    la sección "about" de la home (grep verificado), así que se ajusta
    directo (sin variant aditivo) a radio 10px y CERO box-shadow — antes
    rounded-card + shadow-e1/hover:shadow-e2. El hover pasa a un borde que
    cambia de color, mismo lenguaje de feedback sin sombra.
--}}
{{--
    Lote 20 (maqueta 4): en móvil cada tarjeta es ícono en círculo a la
    izquierda, título serif + texto en el centro y flecha en un círculo verde
    suave a la derecha, todo en una fila. Desde sm: vuelve a la versión de
    siempre (ícono arriba, 44px, sin flecha). La flecha es decorativa: la
    tarjeta no es un enlace.
--}}
<div {{ $attributes->class(['grid h-full min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-1.5 rounded-tile border border-line bg-surface p-2 transition-colors duration-300 hover:border-gold sm:flex sm:flex-col sm:items-stretch sm:gap-3 sm:p-5']) }}>
    <span class="flex h-7 w-7 items-center justify-center rounded-full text-gold-deep ring-1 ring-gold sm:h-11 sm:w-11" aria-hidden="true">
        {!! $icon !!}
    </span>
    <div class="min-w-0 sm:contents">
        <h3 class="min-w-0 break-words font-display text-xs font-semibold leading-tight text-ink sm:text-h3">{{ $title }}</h3>
        @if($description)
            <p class="mt-1 break-words text-[11px] leading-snug text-text-2 sm:mt-0 sm:text-sm sm:leading-relaxed">{{ $description }}</p>
        @endif
    </div>
    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-50 text-action sm:hidden" aria-hidden="true">
        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </span>
</div>
