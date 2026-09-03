@props([
    'icon', // slot HTML del ícono (mismo patrón que x-ui.button/icon)
])
{{--
    Ítem de la franja de confianza del hero (A9, nuevo en el lote 1). Icono +
    etiqueta corta. Es texto de marketing genérico, no un dato ni una cifra
    (no cae bajo la regla de "cero cifras inventadas": no hay número que
    respaldar). El texto viene de lang/es/site.php, nunca cableado acá.
--}}
<div {{ $attributes->class(['flex items-center gap-2 text-xs text-text-2 sm:text-sm']) }}>
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-action" aria-hidden="true">
        {!! $icon !!}
    </span>
    <span>{{ $slot }}</span>
</div>
