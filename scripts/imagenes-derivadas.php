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
 * Además produce la miniatura de compartido (og:image) a 1200x630 exactos.
 * Esa sí es JPG y no WebP: hay clientes de mensajería y redes que todavía no
 * previsualizan WebP, y una miniatura que no se ve es peor que una pesada.
 *
 * Uso:  php scripts/imagenes-derivadas.php [--force]
 */

$root = dirname(__DIR__);
$force = in_array('--force', $argv, true);

/*
 * Miniatura de compartido. origen => [destino, foco vertical 0..1].
 * El foco es el mismo que usa la pantalla donde vive la foto
 * (object-position: center 42% en el hero de la home), para que la miniatura
 * y el hero muestren el mismo encuadre.
 */
const OG_WIDTH = 1200;
const OG_HEIGHT = 630;
const OG_QUALITY = 82;

$ogTargets = [
    'public/images/site/hero-machupicchu-amanecer.jpg' => ['public/images/site/og-default.jpg', 0.42],
];

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

/*
 * ---------------------------------------------------------------------------
 * Miniatura de compartido (og:image), 1200x630 EXACTOS.
 *
 * Recorte tipo "cover": se escala por el lado que falta y se recorta el otro
 * con el foco declarado, nunca se deforma. Con un origen 1920x1299 (ratio
 * 1.478) y un destino 1.905, manda el ancho: se usan los 1920 px completos y
 * se recorta la altura a 1008 px, tomados alrededor del 42% de la foto.
 * Un recorte así aguanta que se cambie el archivo de origen mientras siga
 * siendo apaisado: no hay coordenadas cableadas, solo una proporción.
 * ---------------------------------------------------------------------------
 */
foreach ($ogTargets as $relSrc => [$relOut, $focus]) {
    $src = $root.'/'.$relSrc;
    $out = $root.'/'.$relOut;

    if (! is_file($src)) {
        fwrite(STDERR, "aviso: no existe {$relSrc}, se omite la miniatura de compartido\n");

        continue;
    }

    if (! $force && is_file($out) && filemtime($out) >= filemtime($src)) {
        [$w, $h] = getimagesize($out);
        printf("og:image ya al día: %s (%dx%d, %d KB)\n", $relOut, $w, $h, filesize($out) / 1024);

        continue;
    }

    [$srcW, $srcH] = getimagesize($src);
    $targetRatio = OG_WIDTH / OG_HEIGHT;

    if ($srcW / $srcH > $targetRatio) {
        // Origen más apaisado que el destino: manda el alto, se recorta a los lados.
        $cropH = $srcH;
        $cropW = (int) round($srcH * $targetRatio);
        $cropX = (int) round(($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        // Origen más alto: manda el ancho, se recorta arriba/abajo con el foco.
        $cropW = $srcW;
        $cropH = (int) round($srcW / $targetRatio);
        $cropX = 0;
        // Misma semántica que CSS object-position: center {focus}% -- se alinea
        // el punto "focus" de la foto con el punto "focus" de la caja. Así la
        // miniatura y el hero de la home enseñan exactamente el mismo encuadre.
        $cropY = (int) round(max(0, min($srcH - $cropH, ($srcH - $cropH) * $focus)));
    }

    $source = imagecreatefromjpeg($src);
    $canvas = imagecreatetruecolor(OG_WIDTH, OG_HEIGHT);
    imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, OG_WIDTH, OG_HEIGHT, $cropW, $cropH);
    imagejpeg($canvas, $out, OG_QUALITY);
    imagedestroy($canvas);
    imagedestroy($source);

    [$w, $h] = getimagesize($out);
    printf(
        "og:image generada: %s (%dx%d, %d KB) desde %s recortando %dx%d en y=%d\n",
        $relOut,
        $w,
        $h,
        filesize($out) / 1024,
        basename($relSrc),
        $cropW,
        $cropH,
        $cropY
    );
}
