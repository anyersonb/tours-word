@props([
    'penCents',
    'usdCents',
    'prefix' => null, // p.ej. "Desde"
])
@php
    use App\Support\Money;

    $pen = Money::pen((int) $penCents)->format();
    $usd = Money::usd((int) $usdCents)->format();
@endphp
{{--
    Único punto del front que muestra un precio en dos monedas. Todo el
    formateo pasa por App\Support\Money (regla dura del proyecto); acá solo
    se decide CUÁL de los dos spans se ve, según el store global de moneda
    del header — nunca se calcula ni formatea nada a mano.
--}}
<span {{ $attributes->class(['inline-flex items-baseline gap-1']) }} x-data>
    @if($prefix)
        <span class="text-xs text-text-muted">{{ $prefix }}</span>
    @endif
    <span x-show="$store.currency.code === 'PEN'" x-cloak>{{ $pen }}</span>
    <span x-show="$store.currency.code === 'USD'" x-cloak>{{ $usd }}</span>
    <noscript>{{ $pen }}</noscript>
</span>
