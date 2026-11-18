@props([
    'variant' => 'horizontal', // se conserva por compatibilidad: el logo vigente es un único raster
    'class' => '',
    'width' => 85,
    'height' => 66,
])
{{--
    Único componente que conoce el logo de la marca. Hoy es el recorte
    PROVISIONAL del PDF de diseño (216x168 px, fondo blanco, ver
    public/img/provisional/LEEME.md): hace falta el SVG/AI de la clienta. Al
    llegar, cambiar solo config('brand.logo') (y volver a inlinear si se
    quiere heredar color). asset() respeta el subdirectorio de despliegue.
--}}
<img
    src="{{ asset(config('brand.logo')) }}"
    alt="{{ config('brand.name') }}"
    width="{{ $width }}"
    height="{{ $height }}"
    decoding="async"
    {{ $attributes->class([$class]) }}
>
