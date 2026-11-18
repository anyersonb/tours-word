<?php

namespace App\Support;

/**
 * Placeholder SVG local (data URI base64, sin red) para cuando el catálogo
 * aún no tiene foto real: Tours sin TourImage, Destinations, Experiences y
 * miembros del equipo sin foto.
 *
 * Estética Inka Palace: fondo crema, chakana sutil en café a baja opacidad y
 * acento dorado. Sin texto: el $label se usa solo como <title> accesible.
 */
final class PlaceholderImage
{
    public static function svg(int $width, int $height, string $label, string $hex = 'e0a63a'): string
    {
        $accent = '#'.(preg_match('/^#?[0-9a-fA-F]{6}$/', $hex) ? ltrim($hex, '#') : 'e0a63a');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $size = max(40, (int) round(min($width, $height) * 0.45));
        $cx = $width / 2;
        $cy = $height / 2;
        $u = $size / 10; // unidad de la chakana (cruz escalonada)

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" role="img">
            <title>{$safeLabel}</title>
            <rect width="100%" height="100%" fill="#f7f4ef" />
            <g transform="translate({$cx} {$cy}) scale({$u})" fill="#291b12" fill-opacity="0.10">
                <path d="M-1.5 -5 H1.5 V-3.5 H3 V-1.5 H5 V1.5 H3 V3.5 H1.5 V5 H-1.5 V3.5 H-3 V1.5 H-5 V-1.5 H-3 V-3.5 H-1.5 Z" />
            </g>
            <circle cx="{$cx}" cy="{$cy}" r="{$u}" fill="{$accent}" fill-opacity="0.85" />
            <rect x="0" y="0" width="100%" height="4" fill="{$accent}" fill-opacity="0.7" />
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
