<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleXMLElement;
use Tests\TestCase;

/**
 * S-01 (docs/lote-1/seo-2026-09-02.md, Bloque 1): sitemap.xml servido en
 * vivo, sin prefijo de idioma propio, con solo las páginas indexables de
 * hoy (home, nosotros, contacto) y preparado para que EN/PT-BR entren
 * solos cuando config('cms.active_locales') los active -- sin tocar este
 * archivo ni el controlador.
 *
 * 2026-09-14 -- LOS DOS ESTADOS DE LA BANDERA. Desde 7d25bf0,
 * config('cms.catalog_demo_content') en true mete "noindex, nofollow" en
 * TODO el sitio público (components/layout.blade.php), así que hay dos
 * comportamientos correctos y distintos que este archivo tiene que
 * congelar, no uno:
 *
 *   bandera ARRIBA (valor de hoy) -> ninguna URL indexable: sitemap 404 y
 *                                    robots.txt sin directiva "Sitemap:".
 *   bandera ABAJO                  -> el sitemap de siempre, con sus URLs,
 *                                    y robots.txt declarándolo.
 *
 * Probar solo el estado de hoy dejaría sin red el día que la clienta
 * cargue contenido real y baje la bandera: nadie notaría que el sitemap no
 * volvió. Y probar solo el estado futuro dejaría volver la contradicción
 * de hoy. Por eso cada grupo lleva una aserción de estado explícita antes
 * de mirar la respuesta.
 *
 * INVARIANTE que NO depende de la bandera y se prueba igual: el permiso de
 * rastreo de robots.txt. Si un rastreador no puede descargar la página,
 * jamás llega a leer el meta noindex que la desindexa.
 */
class SitemapTest extends TestCase
{
    // Este archivo dejo de ser "solo XML": ahora tambien pide las paginas
    // publicas para comprobar su meta robots, asi que necesita una BD en
    // estado conocido.
    use RefreshDatabase;

    /**
     * <urlset> declara un namespace por defecto (sitemaps.org): el xpath
     * de SimpleXML no lo resuelve solo -- hay que registrarlo con un
     * prefijo o "//url/loc" no matchea nunca nada (un xpath que nunca
     * matchea da un array vacío, no un error, así que un test así de
     * ingenuo pasaría en falso con un sitemap vacío o roto).
     *
     * @return list<string>
     */
    private function locsFrom(SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return array_map('strval', $xml->xpath('//s:url/s:loc'));
    }

    /**
     * Baja la bandera para probar el estado "contenido real publicado".
     * Antes comprueba que estaba arriba: si algún día alguien la baja en
     * config/cms.php, este helper dejaría de simular un cambio de estado y
     * los tests del grupo "bandera arriba" pasarían a no probar nada --
     * mejor enterarse por un fallo que por un sitemap que nadie revisó.
     */
    private function publishRealContent(): void
    {
        $this->assertTrue(
            config('cms.catalog_demo_content'),
            'Este test simula BAJAR la bandera; si ya viene abajo desde config/cms.php, revisar el grupo "bandera arriba" de este archivo, que quedó sin probar nada.'
        );

        // is_staging_mirror declarado explícito (M-1, config/cms.php): desde
        // que la bandera falla CERRADA, "no declararla" ya no equivale a
        // false -- este helper simula contenido real publicado en el
        // dominio propio de Pacha Viva, así que necesita la declaración
        // explícita para seguir probando el estado "ambas banderas abajo".
        config(['cms.catalog_demo_content' => false, 'cms.is_staging_mirror' => false]);
    }

    // -----------------------------------------------------------------
    // Bandera ARRIBA (estado de hoy): el sitio entero es noindex.
    // -----------------------------------------------------------------

    /**
     * El defecto que este lote corrige: el sitemap anunciaba /es,
     * /es/nosotros, /es/contacto y sus pares en inglés mientras esas
     * MISMAS seis páginas salían con "noindex, nofollow". Se prueba la
     * contradicción completa -- que las páginas son noindex Y que el
     * sitemap no las anuncia -- y no solo el código de estado, porque una
     * ruta rota también daría 404 y no probaría nada.
     */
    public function test_sitemap_is_not_served_while_the_whole_site_is_noindex(): void
    {
        $this->assertTrue(config('cms.catalog_demo_content'), 'este test depende del valor por defecto (producción); si cambió, revisar el resto de la suite');

        foreach (['/es', '/es/nosotros', '/es/contacto', '/en', '/en/nosotros', '/en/contacto'] as $path) {
            $page = $this->get($path);
            $page->assertOk();
            $page->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }

        $this->get('/sitemap.xml')->assertNotFound();
    }

    /**
     * El 404 del sitemap no puede convertirse a su vez en otra señal
     * contradictoria: robots.txt no debe anunciar un sitemap inexistente
     * (en Search Console eso es un error permanente de "no se pudo leer el
     * sitemap").
     */
    public function test_robots_txt_does_not_announce_a_sitemap_that_is_not_being_served(): void
    {
        $this->assertTrue(config('cms.catalog_demo_content'), 'este test depende del valor por defecto (producción); si cambió, revisar el resto de la suite');

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertDontSeeText('Sitemap:');
        $response->assertDontSeeText('sitemap.xml');
    }

    /**
     * INVARIANTE, no bandera: aunque todo el sitio esté en noindex, el
     * rastreo sigue PERMITIDO. Bloquearlo con "Disallow: /" conseguiría lo
     * contrario de lo buscado -- las URLs quedarían rastreadas-bloqueadas
     * pero indexables por enlaces externos, sin que nadie pueda leer el
     * noindex que las desindexa.
     */
    public function test_robots_txt_still_allows_crawling_while_the_whole_site_is_noindex(): void
    {
        $this->assertTrue(config('cms.catalog_demo_content'), 'este test depende del valor por defecto (producción); si cambió, revisar el resto de la suite');

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSeeText('User-agent: *');
        $response->assertSeeText('Disallow: /admin');

        // Ninguna de las formas de cerrar el sitio entero. Se comparan
        // líneas EXACTAS y no un substring: "Disallow: /admin" contiene
        // "Disallow: /", así que un assertDontSeeText ingenuo fallaría
        // siempre y no probaría nada.
        $lines = array_map('trim', explode("\n", $response->getContent()));
        $this->assertNotContains('Disallow: /', $lines);
        $this->assertNotContains('Disallow: /es', $lines);
        $this->assertNotContains('Disallow: /en', $lines);
    }

    // -----------------------------------------------------------------
    // Bandera ABAJO: contenido real publicado, el sitemap vuelve solo.
    // -----------------------------------------------------------------

    public function test_sitemap_is_reachable_and_well_formed_xml_once_real_content_is_published(): void
    {
        $this->publishRealContent();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'sitemap.xml debe ser XML válido');
    }

    /**
     * Lote i18n (2026-09-14): "en" pasó a activo por defecto
     * (config('cms.active_locales')), así que las 3 páginas indexables
     * indexan en AMBOS locales activos hoy -- 6 URLs, no 3. El mecanismo
     * dinámico se sigue probando aparte, con un locale que SÍ sigue
     * inactivo (ver test_activating_a_new_locale_adds_its_urls más abajo).
     */
    public function test_sitemap_lists_exactly_the_six_indexable_pages_in_spanish_and_english(): void
    {
        $this->publishRealContent();

        $response = $this->get('/sitemap.xml');

        $xml = simplexml_load_string($response->getContent());
        $locs = $this->locsFrom($xml);

        $base = rtrim(config('app.url'), '/');

        $this->assertEqualsCanonicalizing([
            "{$base}/es",
            "{$base}/es/nosotros",
            "{$base}/es/contacto",
            "{$base}/en",
            "{$base}/en/nosotros",
            "{$base}/en/contacto",
        ], $locs);
    }

    /**
     * La contrapartida del test de "bandera arriba": no basta con que el
     * sitemap liste URLs -- esas URLs tienen que ser indexables de verdad.
     * Si alguien vuelve a dejar un noindex de sitio colgado de otro sitio,
     * esto lo canta desde el lado del sitemap.
     */
    public function test_every_url_the_sitemap_lists_is_actually_indexable(): void
    {
        $this->publishRealContent();

        $xml = simplexml_load_string($this->get('/sitemap.xml')->getContent());
        $locs = $this->locsFrom($xml);

        $this->assertNotEmpty($locs, 'sin URLs no hay nada que verificar: el test estaría pasando en vacío');

        foreach ($locs as $loc) {
            $page = $this->get($loc);
            $page->assertOk();
            $page->assertDontSee('noindex', false);
        }
    }

    /**
     * Control negativo: la guía de estilos es noindex y nunca debe
     * aparecer, ni nada bajo /admin (el panel de Filament).
     */
    public function test_sitemap_never_lists_the_styleguide_or_the_admin_panel(): void
    {
        $this->publishRealContent();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertDontSee('_styleguide', false);
        $response->assertDontSee('/admin', false);
    }

    /**
     * Lote i18n (2026-09-14): "en" ya está activo por defecto, así que
     * este test usa "pt_BR" -- el único locale del ESQUEMA que sigue
     * inactivo -- para seguir probando el mecanismo DINÁMICO ("si se
     * activa un locale, el sitemap lista sus URLs solo, sin tocar el
     * controlador"), no una lista cableada. Si se usara "en" ahora sería
     * una tautología (ya está activo por defecto y no probaría nada).
     */
    public function test_activating_a_new_locale_adds_its_urls_without_touching_the_mechanism(): void
    {
        $this->publishRealContent();
        config(['cms.active_locales' => ['es', 'en', 'pt_BR']]);

        $response = $this->get('/sitemap.xml');

        $xml = simplexml_load_string($response->getContent());
        $locs = $this->locsFrom($xml);

        $base = rtrim(config('app.url'), '/');

        $this->assertContains("{$base}/pt-br", $locs);
        $this->assertContains("{$base}/pt-br/nosotros", $locs);
        $this->assertContains("{$base}/pt-br/contacto", $locs);
        $this->assertCount(9, $locs);
    }

    /**
     * Fix 2 + 5 (lote SEO): robots.txt pasó de archivo ESTÁTICO (con un
     * host cableado a mano, "http://127.0.0.1:8000/sitemap.xml" -- Defecto
     * 2 del CRO / S-04 del SEO) a ruta dinámica (App\Http\Controllers\
     * RobotsController), exactamente como sitemap.xml. public/robots.txt
     * ya no existe -- $this->get('/robots.txt') SÍ lo enruta ahora.
     */
    public function test_robots_txt_declares_the_sitemap_and_blocks_the_admin_panel(): void
    {
        $this->publishRealContent();

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSeeText('Disallow: /admin');
        $response->assertSeeText('Sitemap: '.url('/sitemap.xml'));
    }

    /**
     * Defecto 2/5 del CRO: el "Sitemap:" NUNCA debe volver a quedar
     * cableado a un host muerto -- tiene que reflejar el host REAL de la
     * request entrante, exactamente como canonical/hreflang ya lo hacen en
     * components/layout.blade.php. Se prueba con un host distinto al de
     * APP_URL (127.0.0.1:8000 en este entorno) para que el test falle de
     * verdad si algún día alguien vuelve a cablear el dominio.
     */
    public function test_robots_txt_sitemap_line_reflects_the_real_request_host_not_a_hardcoded_one(): void
    {
        $this->publishRealContent();

        $response = $this->get('http://otro-host.test/robots.txt');

        $response->assertOk();
        $response->assertSeeText('Sitemap: http://otro-host.test/sitemap.xml');
        $response->assertDontSeeText('127.0.0.1:8000');
    }
}
