@props([
    // URL pública de la foto original (JPG). Acepta lo que devuelven
    // TourImage::url() / Destination::coverImageUrl() (absoluta) y también
    // una ruta relativa tipo "/images/site/hero.jpg".
    'src',
    'alt' => '',
    // Dimensiones INTRÍNSECAS del original. Si no se pasan se leen del
    // archivo: nunca se omiten, porque sin width/height el navegador no
    // puede reservar la caja y la página salta al cargar (CLS).
    'width' => null,
    'height' => null,
    // Obligatorio en la práctica: describe cuánto mide la foto EN PANTALLA.
    // Sin esto el navegador asume 100vw y se baja la variante más grande,
    // que es justo el problema que este componente existe para resolver.
    'sizes' => '100vw',
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
    // Clases para el <img>. El contenedor (.photo, aspecto, radios) lo pone
    // la pantalla; acá solo viaja lo que afecta a la imagen.
    'imgClass' => 'h-full w-full object-cover',
    'position' => null, // object-position, p. ej. "center 35%"
])
@php
    /**
     * <picture> con WebP reducido delante y el JPG original de respaldo.
     *
     * Las variantes las produce scripts/imagenes-derivadas.php como
     * `{nombre}-{ancho}.webp` junto al original. Este componente NO las
     * genera ni las asume: mira el disco y sirve lo que encuentra. Si un
     * original no tiene ninguna variante — el caso del día que la clienta
     * suba sus propias fotos por el CMS — se emite un <img> normal con el
     * original. La ausencia de derivadas degrada, nunca rompe.
     *
     * Por qué no se usan los .webp de tamaño completo que ya venían en el
     * banco: medidos, pesan MÁS que el JPG equivalente (destino-cusco:
     * 400 KB vs 328 KB). Ponerlos delante habría engordado la home.
     */
    $isData = str_starts_with($src, 'data:');

    $srcset = null;
    $intrinsicW = $width;
    $intrinsicH = $height;

    if (! $isData) {
        // URL -> ruta en disco. url('/') se quita solo si el src es absoluto
        // hacia este mismo host; una URL a otro dominio no resuelve a disco
        // y cae sola al camino "sin derivadas".
        $relative = ltrim(str_replace(rtrim(url('/'), '/'), '', $src), '/');
        $relative = strtok($relative, '?');
        $absolute = public_path($relative);

        if (preg_match('/\.jpe?g$/i', $absolute) && is_file($absolute)) {
            if ($intrinsicW === null || $intrinsicH === null) {
                $size = @getimagesize($absolute);

                if ($size !== false) {
                    $intrinsicW ??= $size[0];
                    $intrinsicH ??= $size[1];
                }
            }

            /*
             * Nombres EXACTOS, no glob con comodín. Con
             * glob("destino-cusco-*.webp") entraban también
             * "destino-cusco-galeria-03-1200.webp": el comodín cruza fotos
             * distintas que comparten prefijo, y la tarjeta de Cusco habría
             * servido en srcset la foto de su galería. Comprobado en disco
             * antes de escribir esto.
             */
            $stem = preg_replace('/\.jpe?g$/i', '', $absolute);
            $stemName = basename($stem);
            $variants = [];

            foreach (glob($stem.'-*.webp') as $candidate) {
                // Anclado al nombre COMPLETO del original: "destino-cusco-800.webp"
                // pasa, "destino-cusco-galeria-03-1200.webp" no.
                if (preg_match('/^'.preg_quote($stemName, '/').'-(\d{3,4})\.webp$/', basename($candidate), $m)) {
                    $variants[(int) $m[1]] = basename($candidate);
                }
            }

            if ($variants !== []) {
                ksort($variants);
                $baseUrl = rtrim(str_replace(basename($src), '', $src), '/');

                $srcset = collect($variants)
                    ->map(fn (string $file, int $w) => $baseUrl.'/'.$file.' '.$w.'w')
                    ->implode(', ');
            }
        }
    }

    $imgAttributes = array_filter([
        'loading' => $loading,
        'decoding' => $decoding,
        'fetchpriority' => $fetchpriority,
        'width' => $intrinsicW,
        'height' => $intrinsicH,
    ], fn ($value) => $value !== null && $value !== '');
@endphp
@if($srcset)
    <picture {{ $attributes }}>
        <source type="image/webp" srcset="{{ $srcset }}" sizes="{{ $sizes }}">
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            class="{{ $imgClass }}"
            @if($position) style="object-position: {{ $position }}" @endif
            @foreach($imgAttributes as $name => $value) {{ $name }}="{{ $value }}" @endforeach
        >
    </picture>
@else
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        class="{{ $imgClass }}"
        @if($position) style="object-position: {{ $position }}" @endif
        @foreach($imgAttributes as $name => $value) {{ $name }}="{{ $value }}" @endforeach
        {{ $attributes }}
    >
@endif
