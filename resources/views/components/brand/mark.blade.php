@props([
    'variant' => 'full', // full (logo oficial apilado: cabecera y pie) | horizontal (ilustración + texto en línea)
    'class' => '',
    'sizes' => '(min-width: 1024px) 22rem, 80vw', // solo para "full"
    'eager' => true,
])
@php
    /**
     * Único componente que conoce el logotipo de la marca (INKA PALACE
     * Travel). Si el nombre o el diseño cambian, es el único archivo que se
     * toca.
     *
     * El logotipo es el arte oficial, tal cual, como imagen (nunca redibujado):
     * fondo transparente por keying del marfil del JPEG original. El texto es
     * espresso sobre marfil/blanco, así que SOLO se lee sobre fondos claros;
     * sobre superficies oscuras (pie) va dentro de una placa marfil.
     *
     * horizontal: ilustración a la izquierda y "INKA PALACE / TRAVEL" a la
     *   derecha. Son recortes de los mismos píxeles del archivo oficial,
     *   recolocados (sin redibujar): en un alto de 44px el logo apilado
     *   dejaría el texto en ~7px de mayúscula, ilegible.
     * full: el logo apilado completo (páginas de error, pie).
     *
     * Archivos: public/images/brand/inka-palace-logo*.
     */
    $name = config('app.name');
    $base = asset('images/brand/inka-palace-logo');
@endphp
@if($variant === 'full')
<picture>
    <source type="image/webp" srcset="{{ $base }}-640.webp 640w, {{ $base }}.webp 1054w" sizes="{{ $sizes }}">
    <img src="{{ $base }}.png" srcset="{{ $base }}-640.png 640w, {{ $base }}.png 1054w" sizes="{{ $sizes }}"
         width="1054" height="732" alt="{{ $name }}" class="{{ $class }}"
         @unless($eager) loading="lazy" @endunless decoding="async">
</picture>
@else
<picture>
    <source type="image/webp" srcset="{{ $base }}-horizontal-1x.webp 1x, {{ $base }}-horizontal.webp 2x">
    <img src="{{ $base }}-horizontal-1x.png" srcset="{{ $base }}-horizontal-1x.png 1x, {{ $base }}-horizontal.png 2x"
         width="575" height="100" alt="{{ $name }}" class="{{ $class }}" decoding="async">
</picture>
@endif
