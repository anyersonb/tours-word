<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el idioma SOLO desde el segmento de la URL (/es/, /en/,
 * /pt-br/) -- lote 1 ronda 2, S-08 del informe SEO
 * (docs/lote-1/seo-2026-09-02.md, Bloque 6). Nunca sesión, cookie ni
 * Accept-Language: ese es el antipatrón que el contrato del proyecto pide
 * evitar, porque cada idioma necesita su propia URL indexable. El día que
 * el lote 5 traiga EN/PT-BR reales, este middleware no cambia.
 *
 * Decisión documentada (Anyerson, 2026-09-02): si el segmento corresponde
 * a un locale del ESQUEMA (config('cms.locales')) pero todavía no está
 * activo (config('cms.active_locales')), la respuesta es 404, NO una
 * redirección a /es/. Redirigir simularía que "/en/" ya existe sirviendo
 * contenido en español bajo una URL que dice ser inglesa -- justo la
 * inconsistencia de hreflang que el propio informe advierte evitar. Cuando
 * el lote 5 active "en"/"pt_BR" en config/cms.php, esas rutas empiezan a
 * responder 200 sin tocar este archivo.
 */
class SetLocaleFromUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $segment = (string) $request->route('locale');

        $locale = Locale::fromSegment($segment);

        if ($locale === null || ! Locale::isActive($locale)) {
            abort(404);
        }

        App::setLocale($locale);

        // Permite que route('about'), route('contact'), etc. sigan
        // generando URLs correctas sin que ninguna vista pase 'locale' a
        // mano -- mandato del lote: conservar los nombres de ruta tal cual
        // estaban antes del prefijo. Se normaliza con toSegment() para que
        // "/ES/nosotros" no filtre mayúsculas hacia los enlaces generados.
        URL::defaults(['locale' => Locale::toSegment($locale)]);

        return $next($request);
    }
}
