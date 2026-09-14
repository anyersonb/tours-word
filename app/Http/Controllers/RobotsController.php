<?php

namespace App\Http\Controllers;

use App\Support\Indexability;
use Illuminate\Http\Response;

/**
 * Fix 2 + 5 (auditoria SEO, lote SEO): robots.txt en vivo, igual que
 * SitemapController -- ver su docblock para el razonamiento completo de
 * "por que dinamico, no estatico".
 *
 * Hasta ahora public/robots.txt era un archivo ESTATICO con
 * "Sitemap: http://127.0.0.1:8000/sitemap.xml" cableado a mano (Defecto 2
 * del CRO / S-04 del SEO): un rastreador real recibia un host muerto en
 * cuanto el sitio se sirviera desde otro dominio, porque nadie recuerda
 * editar un archivo estatico en cada despliegue -- el mismo patron que ya
 * rompio og:image y canonical en otro proyecto de la casa cuando APP_URL
 * quedo desalineado con el host real.
 *
 * `url('/sitemap.xml')` usa el Host real de la request entrante (igual que
 * `url()->current()` en components/layout.blade.php), asi que el dia que
 * exista un dominio esto se corrige solo -- no hay nada que editar a mano
 * el dia del despliegue. El archivo public/robots.txt se borro: si siguiera
 * ahi, Apache lo serviria directo (RewriteCond %{REQUEST_FILENAME} !-f en
 * public/.htaccess) y esta ruta nunca se alcanzaria.
 *
 * PENDIENTE el día que exista el dominio real: nada que hacer aquí -- este
 * controlador ya no cablea ningún host. Sólo falta que APP_URL en el .env
 * de producción apunte al dominio real (alimenta también
 * config('filesystems.disks.public.url'), ver Defecto 5 del CRO), y que
 * quien apruebe el copy de robots.txt confirme si hay más rutas a
 * bloquear además de /admin.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        // INVARIANTE: el permiso de rastreo NO depende de ninguna bandera.
        // "User-agent: *" + "Disallow: /admin" se emiten siempre, en todo
        // estado del sitio, porque poder descargar la página es condición
        // NECESARIA para que el rastreador llegue a leer su meta noindex.
        // Un "Disallow: /" aquí conseguiría lo contrario de lo que se
        // busca: la URL quedaría rastreada-bloqueada pero indexable por
        // enlaces externos, sin forma de leer el noindex que la desindexa.
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
        ];

        // La directiva "Sitemap:" sí cuelga de la misma bandera que el
        // propio sitemap (App\Support\Indexability): mientras no haya
        // ninguna URL indexable, SitemapController responde 404 y anunciar
        // aquí una URL que no existe sería otra señal contradictoria (y un
        // error permanente de "no se pudo leer el sitemap" en Search
        // Console). Cuando la bandera baje, la línea vuelve sola.
        if (Indexability::siteIsIndexable()) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.url('/sitemap.xml');
        }

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
