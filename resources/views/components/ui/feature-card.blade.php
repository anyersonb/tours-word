@props([
    'icon', // slot HTML del ícono (mismo patrón que x-ui.button/icon)
    'title',
    'description' => null,
])
{{--
    Caja de atributo de la sección "¿Por qué elegir viajar con nosotros?"
    (A9, nuevo en el lote 1): ícono + título + descripción corta, sin foto.
    Copy de marketing genérico (viene de lang/es/site.php), no dato de CMS.
--}}
<div {{ $attributes->class(['flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5 shadow-sm']) }}>
    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-action" aria-hidden="true">
        {!! $icon !!}
    </span>
    <h3 class="font-display text-base font-semibold text-ink">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-text-2">{{ $description }}</p>
    @endif
</div>
