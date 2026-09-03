@props([
    'icon', // slot HTML del ícono
    'title',
    'has' => true, // dato real presente (C2): sin esto, la tarjeta NO se renderiza
])
{{--
    Tarjeta de la columna "Información de contacto" (C2, nuevo en el lote 1).
    Mismo patrón que x-ui.stat/x-ui.stats-strip: el componente decide su
    propia visibilidad a partir de un booleano que ya resolvió el caller
    (dato real de Setting), nunca al revés. El valor visible (teléfono,
    correo, dirección, íconos de redes) llega por el slot, ya con su propio
    enlace/formato — este componente solo pone el marco.
--}}
@if($has)
    <div {{ $attributes->class(['flex items-start gap-3 rounded-2xl border border-line bg-surface p-4']) }}>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-action" aria-hidden="true">
            {!! $icon !!}
        </span>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-ink">{{ $title }}</p>
            <div class="mt-0.5 text-sm text-text-2">{{ $slot }}</div>
        </div>
    </div>
@endif
