<?php

namespace App\Support;

use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Fuente única de la URL "esta MISMA pantalla, en el otro idioma" que usa el
 * selector de idioma (desplegable de escritorio y pastillas del menú móvil,
 * resources/views/components/site/header.blade.php).
 *
 * DEF-01 (QA visual 2026-09-14, docs/lote-3/qa-visual-2026-09-14.md): esa
 * URL se armaba en el Blade reconstruyendo la ruta actual con los MISMOS
 * parámetros y cambiando solo 'locale'. Es correcto mientras ningún otro
 * parámetro dependa del idioma; deja de serlo en cuanto la ruta lleva un
 * {slug} que sale de una columna JSON traducible: desde "/en/tours/
 * <slug-en>" el enlace "Español" apuntaba a "/es/tours/<slug-en>", que no
 * es la ficha en español de nada -- 404 en los 3 tours que tienen slug
 * propio en inglés. El defecto quedó tapado meses porque los slugs eran
 * idénticos en ambos idiomas hasta 87d8593.
 *
 * El arreglo no es traducir el prefijo mejor, es no adivinar la URL: quien
 * tiene el registro (el controller de la ficha) declara aquí el mapa
 * `locale interno => URL absoluta` con el slug real de cada idioma
 * (ResolvesBySlugByLocale::urlsByLocale()), y el header lo consume. Las
 * pantallas estáticas (home/nosotros/contacto, cuyas URLs no dependen de
 * ningún dato) no declaran nada y siguen con la reconstrucción por defecto,
 * que para ellas sí es correcta.
 *
 * Es un registro por REQUEST (binding `scoped` en AppServiceProvider), no un
 * singleton de proceso: bajo un worker persistente un singleton arrastraría
 * las URLs de la ficha anterior al header de la siguiente request.
 *
 * Este mapa NO es el de hreflang, y por eso no se comparte con x-layout:
 * hreflang solo puede declarar los locales con traducción REAL (un alternate
 * hacia una URL noindex es una contradicción, ver el prop "translatedLocales"
 * de components/layout.blade.php), mientras que el selector debe ofrecer
 * SIEMPRE los dos idiomas activos -- un idioma que desaparece del selector
 * según la ficha es peor experiencia que uno que lleva al contenido de
 * respaldo con su aviso.
 */
class LocaleAlternates
{
    /**
     * @var array<string, string>|null locale interno => URL absoluta
     */
    private ?array $declared = null;

    /**
     * @param  array<string, string>  $urls  locale interno => URL absoluta
     */
    public function set(array $urls): void
    {
        $this->declared = $urls;
    }

    /**
     * @return array<string, string> locale interno => URL absoluta, un
     *                               elemento por cada locale activo
     */
    public function urls(): array
    {
        $declared = $this->declared ?? [];

        return collect(config('cms.active_locales', []))
            ->mapWithKeys(fn (string $locale) => [
                $locale => $declared[$locale] ?? $this->rebuildFromCurrentRoute($locale),
            ])
            ->filter(fn (?string $url) => filled($url))
            ->all();
    }

    /**
     * Reconstrucción por defecto: misma ruta y mismos parámetros de la
     * request actual, cambiando solo 'locale'. Válida SOLO para rutas cuyos
     * demás parámetros no cambian por idioma (hoy: home, nosotros, contacto
     * y los tres índices de catálogo, que no llevan slug).
     */
    private function rebuildFromCurrentRoute(string $locale): ?string
    {
        $name = RouteFacade::currentRouteName() ?: 'home';

        $params = array_merge(
            RouteFacade::current()?->parameters() ?? [],
            ['locale' => Locale::toSegment($locale)],
        );

        return route($name, $params);
    }
}
