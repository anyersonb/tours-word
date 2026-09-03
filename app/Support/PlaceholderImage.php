<?php

namespace App\Support;

/**
 * Local SVG placeholder (data URI, no network) used wherever the catalog
 * doesn't have a real photo yet — today that's every Tour without a
 * TourImage row, and every Destination/Experience, since neither model has
 * an image column at all (schema gap, flagged for a future lote).
 *
 * Same technique already used ad hoc inside resources/views/styleguide.blade.php
 * (etapa A, not touched by this lote). That copy is intentionally left as-is
 * to avoid touching an etapa A file; this class is the shared version the
 * Home (etapa B) consumes, so a future cleanup can point the styleguide at
 * this same class without any visual change.
 */
final class PlaceholderImage
{
    public static function svg(int $width, int $height, string $label, string $hex = '2c6fa8'): string
    {
        $hex = ltrim($hex, '#');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            <rect width="100%" height="100%" fill="#{$hex}" />
            <text x="50%" y="50%" fill="#ffffff" font-family="sans-serif" font-size="14" text-anchor="middle" dominant-baseline="middle">{$safeLabel}</text>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
