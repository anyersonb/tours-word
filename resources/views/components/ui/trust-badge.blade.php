@props([
    'icon', // slot HTML del ícono (mismo patrón que x-ui.button/icon)
    // light -> sobre --surface/--ground/--sand · dark -> sobre foto con scrim
    'tone' => 'light',
])
@php
    $tones = [
        'light' => ['text' => 'text-text-2', 'chip' => 'bg-brand-50 text-action'],
        // Medido: --on-dark-2 (#c6d6cd) sobre el scrim del hero pasa AA de
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
<div {{ $attributes->class(['flex items-center gap-2 text-xs sm:text-sm', $t['text']]) }}>
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $t['chip'] }}" aria-hidden="true">
        {!! $icon !!}
    </span>
    <span>{{ $slot }}</span>
</div>
