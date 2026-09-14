<?php

namespace App\Models\Concerns;

/**
 * Objetivo 2 (lote i18n, 2026-09-14). Decision de producto: un tour/destino/
 * experiencia que existe pero no tiene su traduccion al locale de la URL
 * NUNCA muestra un campo en blanco ni un 404 -- cae al locale de respaldo
 * (config('app.fallback_locale'), hoy "es"). Esto cubre dos capas:
 *
 *   1. El SLUG: si no hay slug->en, se busca por slug->es. Sin esto,
 *      "/en/tours/<slug-es>" daria 404 aunque el tour SI exista, porque un
 *      `where("slug->en", $slug)` nunca matchea un slug que solo vive bajo
 *      la clave "es" del JSON.
 *   2. El resto de los campos traducibles (title/description/itinerary...):
 *      Spatie\Translatable ya resuelve esto solo -- HasTranslations::
 *      getAttributeValue() usa config('app.fallback_locale') (= "es" en
 *      .env/.env.example de este proyecto) cuando el locale pedido no tiene
 *      traduccion. No hay que tocar nada para que $tour->title devuelva el
 *      texto en espanol en vez de null.
 *
 * `slug->{$locale}` interpola el locale directo en la ruta del JSON, igual
 * que Tour::slugTaken() (ver su docblock): seguro solo porque quien llama
 * (los Controllers) ya valido $locale contra config('cms.active_locales')
 * en SetLocaleFromUrl antes de llegar aca. config('app.fallback_locale') es
 * configuracion de la app, no input de la request -- misma garantia.
 */
trait ResolvesBySlugByLocale
{
    /**
     * @param  array<int, string>|string  $with
     */
    public static function findBySlugForLocale(string $locale, string $slug, array|string $with = []): ?static
    {
        $fallbackLocale = (string) config('app.fallback_locale');

        foreach (array_unique([$locale, $fallbackLocale]) as $tryLocale) {
            $model = static::query()
                ->published()
                ->where("slug->{$tryLocale}", $slug)
                ->with($with)
                ->first();

            if ($model) {
                return $model;
            }
        }

        return null;
    }

    /**
     * True si $field (el campo de contenido principal: "title" en Tour,
     * "name" en Destination/Experience) NO tiene traduccion propia para
     * $locale -- la vista esta mostrando el contenido en su idioma
     * original (fallback), no en $locale, y debe decirlo en el HTML
     * (atributo lang del bloque de contenido, nunca fingir que es $locale).
     */
    public function isContentFallbackFor(string $locale, string $field): bool
    {
        return ! in_array($locale, $this->getTranslatedLocales($field), true);
    }
}
