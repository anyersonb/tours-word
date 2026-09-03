@props([
    'icon', // slot HTML del ícono (mismo patrón que x-ui.feature-card/x-ui.hero-attribute)
    'title',
    'description' => null,
])
{{--
    Ítem de "Nuestros valores" (D, lote 1/etapa D, Nosotros): ícono + título +
    descripción SIN tarjeta (sin borde/sombra/fondo propio), a diferencia de
    x-ui.feature-card. El mockup dibuja los 4 valores como una fila plana
    sobre el fondo --ground de la sección, no como tarjetas — de ahí el
    componente nuevo en vez de reusar feature-card con estilos distintos.
--}}
<div {{ $attributes->class(['flex flex-col gap-3']) }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-action" aria-hidden="true">
        {!! $icon !!}
    </span>
    <div>
        <h3 class="font-display text-base font-semibold text-ink">{{ $title }}</h3>
        @if($description)
            <p class="mt-1 text-sm text-text-2">{{ $description }}</p>
        @endif
    </div>
</div>
