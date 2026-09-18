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
 *
 * Receta SEO `docs/rediseno-2026/02-seo.md` ## 6.2 (staging en
 * limaviewtours.com/tour-word/, dominio de otro cliente): segunda bandera
 * independiente, `cms.is_staging_mirror`, con su PROPIO ciclo de vida --
 * se apaga cuando el build deja de vivir en ese subdirectorio, no cuando
 * el catálogo pasa a contenido real. A propósito NO se reutiliza
 * `catalog_demo_content` para esto: son dos motivos distintos para no
 * indexar y algún día se apagará uno sin el otro.
 *
 * FALLA CERRADA a propósito (hallazgo M-1, docs/rediseno-2026/04-seguridad.md
 * ##5.4): antes esta comprobación negaba directamente el valor de config
 * (`! config(...)`), así que cualquier valor falsy -- null por clave
 * ausente, "" o "0" por una variable de entorno mal escrita -- se leía
 * como "no es espejo / no es demo" y dejaba el sitio indexable. Ahora se
 * compara cada bandera contra `=== false` en estricto, con `true` como
 * default del propio config() (que solo aplica si la clave está AUSENTE
 * del array resuelto -- el caso de una `bootstrap/cache/config.php` vieja,
 * generada antes de que esta clave existiera). Ante la duda, no indexable.
 */
class Indexability
{
    /**
     * ¿Alguna URL pública de este sitio es indexable hoy?
     *
     * Solo `true` si las DOS banderas resuelven a `false` EXPLÍCITO:
     * `catalog_demo_content` (ya no es contenido de muestra) Y
     * `is_staging_mirror` (este build no es el espejo en el dominio ajeno).
     * Cualquier otro estado de cualquiera de las dos -- true, ausente,
     * vacío, mal escrito -- basta para que el sitio entero salga noindex.
     */
    public static function siteIsIndexable(): bool
    {
        return config('cms.catalog_demo_content', true) === false
            && config('cms.is_staging_mirror', true) === false;
    }
}
