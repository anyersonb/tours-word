<?php

namespace App\Support;

/**
 * Traduce entre el locale interno (clave de config('cms.locales'), ej.
 * "pt_BR") y el segmento que aparece en la URL (ej. "pt-br"), por
 * convención web de slugs en minúscula con guiones (lote 1 ronda 2, S-08
 * del informe SEO: docs/lote-1/seo-2026-09-02.md, Bloque 6).
 *
 * Fuente única de esta conversión: ningún otro archivo debe cablear
 * "pt_BR"/"pt-br" a mano, igual que App\Support\Money es la única fuente
 * de formateo de precios en este proyecto.
 */
class Locale
{
    /**
     * Locale interno (config('cms.locales')) -> segmento de URL.
     */
    public static function toSegment(string $locale): string
    {
        return str_replace('_', '-', strtolower($locale));
    }

    /**
     * Segmento de URL -> locale interno, o null si el segmento no
     * corresponde a ningún locale del ESQUEMA (config('cms.locales'), el
     * universo completo que soportan las columnas traducibles, no solo
     * "active_locales").
     */
    public static function fromSegment(string $segment): ?string
    {
        $segment = strtolower($segment);

        foreach (array_keys(config('cms.locales')) as $locale) {
            if (self::toSegment($locale) === $segment) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Si el locale ya está habilitado para servirse hoy
     * (config('cms.active_locales')). Un locale puede existir en el
     * esquema (fromSegment() lo reconoce) sin estar activo todavía.
     */
    public static function isActive(string $locale): bool
    {
        return in_array($locale, config('cms.active_locales'), true);
    }
}
