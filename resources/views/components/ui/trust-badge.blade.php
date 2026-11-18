@props([
    'icon', // slot HTML del ícono (mismo patrón que x-ui.button/icon)
    // light -> sobre --surface/--ground/--sand · dark -> sobre foto con scrim
    'tone' => 'light',
    // inline (default) -> ícono al lado · stacked -> ícono arriba, texto centrado
    'layout' => 'inline',
])
@php
    $stacked = $layout === 'stacked';
    $tones = [
        'light' => ['text' => 'text-text-2', 'chip' => 'bg-brand-50 text-action'],
        // Medido: --on-dark-2 (#e4dac7) sobre el scrim del hero pasa AA de
        // texto normal con margen; la pastilla del ícono usa blanco puro.
        'dark' => ['text' => 'text-on-dark-2', 'chip' => 'bg-white/15 text-white ring-1 ring-white/20'],
    ];
    $t = $tones[$tone] ?? $tones['light'];
@endphp
{{--
    Ítem de la franja de confianza del hero (A9, lote 1). Icono + etiqueta
    corta. Es texto de marketing genérico, no un dato ni una cifra (no cae
    bajo la regla de "cero cifras inventadas": no hay número que respaldar).
    El texto viene de lang/es/site.php, nunca cableado acá.
--}}
<div {{ $attributes->class([
    'text-xs sm:text-sm',
    $t['text'],
    'flex items-center gap-2' => ! $stacked,
    'flex flex-col items-center gap-2 px-1 text-center leading-tight' => $stacked,
]) }}>
    <span class="flex shrink-0 items-center justify-center rounded-full {{ $stacked ? 'h-11 w-11 [&_svg]:h-5 [&_svg]:w-5' : 'h-8 w-8' }} {{ $t['chip'] }}" aria-hidden="true">
        {!! $icon !!}
    </span>
    <span>{{ $slot }}</span>
</div>
