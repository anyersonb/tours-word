<?php

/**
 * Genera las variantes reducidas en WebP que consume <x-ui.picture>.
 *
 * POR QUÉ EXISTE
 * --------------
 * El banco de fotos trae un .webp por cada .jpg, pero al TAMAÑO ORIGINAL y
 * codificado sin pérdida agresiva: medido, esos .webp pesan MÁS que el .jpg
 * (destino-cusco: 400 KB webp vs 328 KB jpg). Poner ese WebP delante en un
 * <picture> habría dejado la home más pesada, no más liviana. El ahorro real
 * no está en el formato sino en el TAMAÑO: la home servía imágenes de
 * 1200-1920 px dentro de tarjetas de 280-340 px.
 *
 * Este script produce, junto a cada original, `{nombre}-{ancho}.webp` para los
 * anchos que el sitio realmente pinta. El JPG original se conserva intacto y
 * sigue siendo el `src` de respaldo del <img> (navegador sin WebP).
 *
 * IMPORTANTE: es tooling de assets, no backend. No toca base de datos, rutas
 * ni modelos. Si un original no tiene variantes (p. ej. una foto que la
 * clienta suba desde el CMS), <x-ui.picture> sirve el original tal cual: la
 * ausencia de derivadas nunca rompe una pantalla.
 *
 * Uso:  php scripts/imagenes-derivadas.php [--force]
 */

$root = dirname(__DIR__);
$force = in_array('--force', $argv, true);

/** Carpeta => anchos a generar (nunca por encima del ancho original). */
$targets = [
    'storage/app/public/tours'        => [400, 800, 1200],
    'storage/app/public/destinations' => [400, 800, 1200],
    'storage/app/public/experiences'  => [400, 800, 1200],
    'public/images/site'              => [640, 1024, 1440, 1920],
];

const WEBP_QUALITY = 72;

if (! function_exists('imagewebp')) {
    fwrite(STDERR, "GD sin soporte WebP. Abortado.\n");
    exit(1);
}

$made = 0;
$skipped = 0;
$bytesIn = 0;
$bytesOut = 0;

foreach ($targets as $relDir => $widths) {
    $dir = $root.'/'.$relDir;

    if (! is_dir($dir)) {
        fwrite(STDERR, "aviso: no existe {$relDir}, se omite\n");
        continue;
    }

    foreach (glob($dir.'/*.jpg') as $src) {
        // Una derivada nunca es fuente de otra derivada.
        if (preg_match('/-\d{3,4}\.jpg$/', $src)) {
            continue;
        }

        [$srcW, $srcH] = getimagesize($src);
        $bytesIn += filesize($src);

        foreach ($widths as $w) {
            if ($w > $srcW) {
                continue;
            }

            $out = preg_replace('/\.jpg$/', "-{$w}.webp", $src);

            if (! $force && is_file($out) && filemtime($out) >= filemtime($src)) {
                $skipped++;
                $bytesOut += filesize($out);
                continue;
            }

            $img = imagecreatefromjpeg($src);
            $resized = imagescale($img, $w, -1, IMG_BICUBIC);
            imagedestroy($img);

            imagewebp($resized, $out, WEBP_QUALITY);
            imagedestroy($resized);

            $made++;
            $bytesOut += filesize($out);
        }

        // El ancho original también se ofrece en WebP cuando la foto es más
        // chica que el mayor ancho pedido (si no, no habría entrada de
        // srcset que cubra pantallas grandes).
        if (max($widths) > $srcW) {
            $out = preg_replace('/\.jpg$/', "-{$srcW}.webp", $src);

            if ($force || ! is_file($out) || filemtime($out) < filemtime($src)) {
                $img = imagecreatefromjpeg($src);
                imagewebp($img, $out, WEBP_QUALITY);
                imagedestroy($img);
                $made++;
            }

            $bytesOut += filesize($out);
        }
    }
}

printf(
    "Derivadas: %d generadas, %d ya estaban al día.\n  originales %.1f MB -> derivadas %.1f MB\n",
    $made,
    $skipped,
    $bytesIn / 1048576,
    $bytesOut / 1048576
);
