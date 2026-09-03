@props([
    'variant' => 'horizontal', // horizontal | vertical | mono | symbol
    'class' => 'h-8 w-auto',
])
@php
    /**
     * Único componente que conoce el nombre y el logo de la marca. Si el
     * nombre cambia (está EN REVISIÓN, ver docs/lote-0/01-nombres.md), este
     * es el único archivo que se toca — nada de "Pacha Viva" repartido en
     * las vistas.
     *
     * El SVG se INYECTA inline (no <img src>) a propósito: logo.svg y
     * logo-mono.svg usan var(--action, #hex) / currentColor, y un <img src>
     * carga el SVG en un documento aislado que no puede leer las custom
     * properties de esta página — se vería siempre con el hex de emergencia
     * aunque cambiemos --brand-h. Inline sí reacciona en vivo (verificado:
     * ver docs/lote-1/00-sistema-diseno.md, prueba A1).
     */
    $files = [
        'horizontal' => 'logo.svg',
        'vertical' => 'logo-vertical.svg',
        'mono' => 'logo-mono.svg',
        'symbol' => 'simbolo.svg',
    ];

    $file = $files[$variant] ?? $files['horizontal'];
    $path = public_path('images/brand/'.$file);
    $svg = is_file($path) ? file_get_contents($path) : '';

    // Le pasamos la clase de Tailwind al <svg> raíz para que el tamaño se
    // controle igual que cualquier otro elemento del sistema.
    if ($svg !== '') {
        $svg = preg_replace('/<svg /', '<svg class="'.e($class).'" ', $svg, 1);
    }
@endphp
{!! $svg !!}
