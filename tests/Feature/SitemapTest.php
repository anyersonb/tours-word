<?php

namespace Tests\Feature;

use SimpleXMLElement;
use Tests\TestCase;

/**
 * S-01 (docs/lote-1/seo-2026-09-02.md, Bloque 1): sitemap.xml servido en
 * vivo, sin prefijo de idioma propio, con solo las páginas indexables de
 * hoy (home, nosotros, contacto) y preparado para que EN/PT-BR entren
 * solos cuando config('cms.active_locales') los active en el lote 5 --
 * sin tocar este archivo ni el controlador.
 */
class SitemapTest extends TestCase
{
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

    public function test_sitemap_is_reachable_and_well_formed_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'sitemap.xml debe ser XML válido');
    }

    /**
     * Lote i18n (2026-09-14): "en" paso a activo por defecto
     * (config('cms.active_locales')), asi que las 3 paginas indexables
     * indexan en AMBOS locales activos hoy -- 6 URLs, no 3. El mecanismo
     * dinamico se sigue probando aparte, con un locale que SI sigue
     * inactivo (ver test_activating_a_new_locale_adds_its_urls_below).
     */
    public function test_sitemap_lists_exactly_the_six_indexable_pages_in_spanish_and_english(): void
    {
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
     * Control negativo: la guía de estilos es noindex y nunca debe
     * aparecer, ni nada bajo /admin (el panel de Filament).
     */
    public function test_sitemap_never_lists_the_styleguide_or_the_admin_panel(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertDontSee('_styleguide', false);
        $response->assertDontSee('/admin', false);
    }

    /**
     * Lote i18n (2026-09-14): "en" ya esta activo por defecto, asi que este
     * test usa "pt_BR" -- el unico locale del ESQUEMA que sigue inactivo --
     * para seguir probando el mecanismo DINAMICO ("si se activa un locale,
     * el sitemap lista sus URLs solo, sin tocar el controlador"), no una
     * lista cableada. Si se usara "en" ahora seria una tautologia (ya esta
     * activo por defecto y no probaria nada).
     */
    public function test_activating_a_new_locale_adds_its_urls_without_touching_the_mechanism(): void
    {
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
     * public/robots.txt es un archivo ESTÁTICO que el webserver real sirve
     * directo del docroot -- el kernel de testing no lo enruta (por eso
     * $this->get('/robots.txt') daría 404 aquí aunque el archivo exista y
     * el navegador real lo reciba bien). Se lee del disco, como lo vería
     * el rastreador.
     */
    public function test_robots_txt_declares_the_sitemap_and_blocks_the_admin_panel(): void
    {
        $contents = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap:', $contents);
        $this->assertStringContainsString('sitemap.xml', $contents);
        $this->assertStringContainsString('Disallow: /admin', $contents);
    }
}
