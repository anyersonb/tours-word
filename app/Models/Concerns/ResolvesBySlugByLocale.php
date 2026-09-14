<?php

namespace App\Models\Concerns;

use App\Support\Locale;

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
 *
 * ------------------------------------------------------------------------
 * DEF-02 (QA visual 2026-09-14): esa busqueda por locale de respaldo
 * resolvia el 404 pero abria un duplicado -- "/en/tours/<slug-es>" y
 * "/en/tours/<slug-en>" devolvian 200 con el MISMO contenido y canonical
 * autorreferente cada una. Hoy lo tapa el noindex global
 * (config('cms.catalog_demo_content')); el dia que baje, tres pares de URLs
 * compitiendo entre si.
 *
 * El arreglo NO es quitar el fallback (eso devuelve el 404 a todo enlace
 * viejo que ande suelto). Es el mismo criterio que ya aplica
 * tour_slug_histories: la busqueda sigue siendo amplia -- ahora por CUALQUIER
 * locale activo, no solo el de respaldo, porque el caso reportado es el
 * inverso ("/es/tours/<slug-en>") -- y el controller, cuando el registro SI
 * tiene slug propio en el locale de la URL, responde 301 al canonico en vez
 * de 200 (ver canonicalSlugFor()). El enlace viejo sigue vivo y el duplicado
 * desaparece.
 *
 * Cuando el registro NO tiene slug en el locale de la URL no hay canonico al
 * que redirigir: se sirve 200 con el contenido de respaldo, su aviso, su
 * lang="es" honesto y noindex -- la decision de producto de arriba, intacta.
 */
trait ResolvesBySlugByLocale
{
    /**
     * @param  array<int, string>|string  $with
     */
    public static function findBySlugForLocale(string $locale, string $slug, array|string $with = []): ?static
    {
        foreach (static::slugLookupLocales($locale) as $tryLocale) {
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
     * Locales en los que se busca el slug, en orden de prioridad: primero el
     * de la URL (para que un slug que existe en los dos idiomas resuelva
     * SIEMPRE a su propio registro y nunca redirija), despues el resto de
     * los activos y el de respaldo.
     *
     * Todos salen de config, nunca de la request: es la misma garantia que
     * permite interpolarlos en `slug->{$locale}` (ver el docblock de arriba).
     *
     * @return list<string>
     */
    private static function slugLookupLocales(string $locale): array
    {
        return array_values(array_unique(array_merge(
            [$locale],
            array_values((array) config('cms.active_locales', [])),
            [(string) config('app.fallback_locale')],
        )));
    }

    /**
     * El slug canonico de este registro en $locale cuando la URL pedida llego
     * con OTRO (el de otro idioma), o null si la URL pedida ya es la canonica
     * o si el registro no tiene slug propio en $locale -- en ese segundo caso
     * no existe URL canonica en ese idioma a la que redirigir y se sirve el
     * contenido de respaldo (ver el docblock de la clase).
     *
     * Comparar contra el slug PEDIDO (y no contra el locale que matcheo) es
     * lo que hace imposible un bucle de redirecciones: el destino del 301 es,
     * por construccion, un slug para el que este metodo devuelve null.
     */
    public function canonicalSlugFor(string $locale, string $requestedSlug): ?string
    {
        $canonical = $this->getTranslation('slug', $locale, false);

        return filled($canonical) && $canonical !== $requestedSlug ? $canonical : null;
    }

    /**
     * URL de ESTA ficha en cada locale activo, cada una con el slug que el
     * registro tiene en ESE idioma. Es lo que consume el selector de idioma
     * (App\Support\LocaleAlternates, ver su docblock para el porque).
     *
     * Un locale sin slug propio cae al slug del locale de respaldo: esa URL
     * responde 200 con el aviso de contenido sin traducir, que es la decision
     * de producto vigente -- nunca se ofrece un idioma que lleva a un 404, y
     * nunca se ofrece una URL que redirige.
     *
     * @return array<string, string> locale interno => URL absoluta
     */
    public function urlsByLocale(string $routeName): array
    {
        $fallbackLocale = (string) config('app.fallback_locale');

        return collect(config('cms.active_locales', []))
            ->mapWithKeys(function (string $locale) use ($routeName, $fallbackLocale) {
                $slug = $this->getTranslation('slug', $locale, false)
                    ?: $this->getTranslation('slug', $fallbackLocale, false);

                if (blank($slug)) {
                    return [];
                }

                return [$locale => route($routeName, [
                    'locale' => Locale::toSegment($locale),
                    'slug' => $slug,
                ])];
            })
            ->all();
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
