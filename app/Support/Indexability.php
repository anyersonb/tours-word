<?php

namespace App\Support;

/**
 * Fuente única de la pregunta "¿hay algo indexable en este sitio hoy?".
 *
 * Desde el commit 7d25bf0, resources/views/components/layout.blade.php
 * calcula `$isNoindex = $noindex || config('cms.catalog_demo_content')`:
 * mientras esa bandera esté arriba, TODA página pública del sitio sale con
 * `<meta name="robots" content="noindex, nofollow">`, no solo las fichas
 * del catálogo. La home, "nosotros" y "contacto" incluidas.
 *
 * Todo lo que anuncie URLs hacia afuera (sitemap.xml, la directiva
 * "Sitemap:" de robots.txt) tiene que colgar de ESTA misma respuesta, no
 * de una lista paralela que haya que acordarse de sincronizar a mano: un
 * sitemap que lista URLs noindex es una señal contradictoria -- le pide al
 * rastreador que priorice justo lo que la página le dice que no indexe.
 *
 * OJO con el sentido de la bandera: `catalog_demo_content` en true
 * significa "esto es contenido de muestra", es decir NO indexable. Aquí se
 * invierte una sola vez, a propósito, para que los llamadores lean en
 * positivo y nadie tenga que recordar la polaridad.
 *
 * El día que la clienta cargue contenido real y `catalog_demo_content`
 * baje a false en config/cms.php, el sitemap vuelve a anunciar sus URLs y
 * robots.txt vuelve a declararlo, sin tocar una línea de código -- que es
 * exactamente el criterio con el que la bandera se extrajo a config.
 */
class Indexability
{
    /**
     * ¿Alguna URL pública de este sitio es indexable hoy?
     *
     * false mientras el catálogo siga siendo contenido de muestra: en ese
     * estado el layout marca noindex a nivel de SITIO, así que no queda
     * ninguna URL que valga la pena anunciar.
     */
    public static function siteIsIndexable(): bool
    {
        return ! config('cms.catalog_demo_content');
    }
}
