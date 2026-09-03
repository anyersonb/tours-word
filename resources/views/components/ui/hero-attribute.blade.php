@props([
    'icon', // slot HTML del ícono (mismo patrón que x-ui.button/icon y x-ui.trust-badge)
    'title',
    'description' => null,
])
{{--
    Atributo con ícono del hero partido de Contacto (C, nuevo en el lote 1):
    ícono + título + descripción apilados a la derecha, sin tarjeta/borde.
    Distinto de x-ui.feature-card (que sí lleva borde/sombra de tarjeta) y de
    x-ui.trust-badge (una sola línea de texto, sin descripción). El fondo del
    ícono (bg-brand-50/text-action) sí se mantiene igual que el resto del
    sistema, aunque el mockup lo dibuja sin caja — se prioriza la consistencia
    del inventario de íconos sobre la réplica literal del mockup.
--}}
<div {{ $attributes->class(['flex items-start gap-3']) }}>
    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-action" aria-hidden="true">
        {!! $icon !!}
    </span>
    <div>
        <p class="text-sm font-semibold text-ink">{{ $title }}</p>
        @if($description)
            <p class="text-xs text-text-2">{{ $description }}</p>
        @endif
    </div>
</div>
