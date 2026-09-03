<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\Response;

/**
 * Sirve sitemap.xml en vivo, en cada request -- sin paso de build, sin
 * archivo cacheado que regenerar (S-01, docs/lote-1/seo-2026-09-02.md,
 * Bloque 1). Lista solo las rutas públicas indexables (nunca "styleguide",
 * que es noindex, y nunca nada del panel de Filament bajo /admin) cruzadas
 * con config('cms.active_locales'). El día que el lote 5 active "en"/
 * "pt_BR" en config/cms.php, este archivo no cambia: sus URLs aparecen
 * solas en el próximo request. El día que el lote 3 agregue tours/
 * destinos como páginas públicas, se extiende self::ROUTE_NAMES (o se
 * alimenta una query de registros publicados al mismo builder de XML) --
 * la construcción de URLs y el escapado no cambian.
 *
 * URLs absolutas: route()/url() de Laravel usan el Host real de la
 * request entrante cuando existe uno (este controlador siempre corre
 * dentro de una request HTTP real). El único contexto que cae en
 * config('app.url') es generar URLs FUERA de una request HTTP (un comando
 * artisan) -- este controlador no está expuesto a eso, pero igual
 * IMPORTA: APP_URL debe ser el dominio real en producción, porque también
 * alimenta config('filesystems.disks.public.url') (URLs de imágenes,
 * Defecto 5 del CRO) y cualquier generación futura por consola. Ver S-04.
 */
class SitemapController extends Controller
{
    /**
     * Nombres de ruta indexables. "styleguide" queda fuera a propósito:
     * es noindex (ver resources/views/styleguide.blade.php).
     *
     * @var list<string>
     */
    private const ROUTE_NAMES = ['home', 'about', 'contact'];

    public function __invoke(): Response
    {
        $urls = [];

        foreach (config('cms.active_locales') as $locale) {
            $segment = Locale::toSegment($locale);

            foreach (self::ROUTE_NAMES as $name) {
                $urls[] = route($name, ['locale' => $segment]);
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
