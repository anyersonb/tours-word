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

        $canonicalSegment = Locale::toSegment($locale);

        $redirect = $this->canonicalRedirect($request, $canonicalSegment);

        if ($redirect !== null) {
            return $redirect;
        }

        App::setLocale($locale);

        // Permite que route('about'), route('contact'), etc. sigan
        // generando URLs correctas sin que ninguna vista pase 'locale' a
        // mano -- mandato del lote: conservar los nombres de ruta tal cual
        // estaban antes del prefijo.
        URL::defaults(['locale' => $canonicalSegment]);

        return $next($request);
    }

    /**
     * ÚNICO punto del middleware que emite una redirección. Devuelve null
     * cuando la URL pedida ya es la canónica -- y también, por la red de
     * seguridad de más abajo, cuando el destino calculado coincidiría con
     * la URI actual. Concentrarlo en un solo método es lo que hace que la
     * garantía "nunca un bucle" no dependa de acordarse de repetirla en
     * cada normalización que se agregue después.
     */
    private function canonicalRedirect(Request $request, string $canonicalSegment): ?Response
    {
        $canonicalPath = $this->canonicalPath($request, $canonicalSegment);

        if ($canonicalPath === null) {
            return null;
        }

        $query = (string) $request->getQueryString();

        $redirect = redirect()->to($canonicalPath.($query !== '' ? '?'.$query : ''), 301);

        // GARANTÍA ESTRUCTURAL -- M-01 (docs/lote-3/seguridad-enrutado-2026-09-14.md).
        //
        // El bucle infinito de 301 sobre "/%45%53/nosotros" ocurrió porque
        // las dos mitades del guard leían representaciones distintas del
        // mismo segmento: se decidía redirigir comparando el segmento
        // DECODIFICADO ("ES" != "es") y se calculaba el destino reescribiendo
        // el path CRUDO ("/%45%53/nosotros", que no casaba con el patrón).
        // Arreglar el cálculo (canonicalPath(), abajo) cierra ESE fallo;
        // este bloque cierra la FAMILIA entera.
        //
        // La regla: este middleware no puede emitir jamás un Location igual
        // a la URI que se le pidió. Si el destino calculado coincide con la
        // petición, no se redirige: se sirve el contenido. Así, una futura
        // normalización mal escrita (idioma, mayúsculas, barra final,
        // codificación) puede como mucho servir una URL no canónica -- un
        // 200 o un 404 --, nunca un bucle, que además el navegador cachea de
        // forma indefinida por ser 301 y sobrevive al arreglo del servidor.
        //
        // Se compara contra el Location REALMENTE emitido (getTargetUrl(),
        // ya normalizado por el generador de URLs) y no contra la cadena que
        // se le pasó: lo único que puede provocar el bucle es lo que ve el
        // cliente. La comparación ignora la barra final porque Laravel
        // enruta "/es" y "/es/" a la misma ruta: una redirección que solo
        // difiriera en eso sería un salto de más hacia el mismo destino.
        if ($this->pointsToItself($redirect->getTargetUrl(), $request, $query)) {
            return null;
        }

        return $redirect;
    }

    /**
     * Path canónico construido desde los segmentos YA DECODIFICADOS
     * ($request->segments()) y recodificados uno a uno, en lugar de
     * reescribir el path crudo con una expresión regular. Es lo que hace
     * que "/%45%53/nosotros", "/e%73/nosotros" y "/Es/nosotros" normalicen
     * igual de bien que "/ES/nosotros".
     *
     * Como el segmento 0 se REEMPLAZA por el locale canónico, el resultado
     * empieza siempre por "/es", "/en", ...: no puede empezar por "//" ni
     * salir del origen, y rawurlencode() neutraliza de paso cualquier CRLF
     * o barra que viniera dentro de un segmento.
     */
    private function canonicalPath(Request $request, string $canonicalSegment): ?string
    {
        $segments = $request->segments();

        if ($segments === []) {
            return null;
        }

        $segments[0] = $canonicalSegment;

        $path = '/'.implode('/', array_map('rawurlencode', $segments));

        // La home del locale se sirve en "/es/" y route('home') genera
        // "/es": sin preservar la barra final, cada portada se llevaría un
        // 301 de más.
        if (str_ends_with($request->getPathInfo(), '/')) {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Si el Location que se va a emitir es, a todos los efectos, la misma
     * URI que se pidió.
     */
    private function pointsToItself(string $location, Request $request, string $query): bool
    {
        $host = $request->getSchemeAndHttpHost();
        $suffix = $query !== '' ? '?'.$query : '';

        $current = [
            // Path crudo tal como llegó, con el query ya normalizado.
            $host.$request->getPathInfo().$suffix,
            // Y el request URI crudo entero, por si el query no se normaliza.
            $host.$request->getRequestUri(),
        ];

        foreach ($current as $uri) {
            if ($location === $uri || rtrim($location, '/') === rtrim($uri, '/')) {
                return true;
            }
        }

        return false;
    }
}
