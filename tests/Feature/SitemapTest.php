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

    public function test_sitemap_lists_exactly_the_three_indexable_spanish_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $xml = simplexml_load_string($response->getContent());
        $locs = $this->locsFrom($xml);

        $base = rtrim(config('app.url'), '/');

        $this->assertEqualsCanonicalizing([
            "{$base}/es",
            "{$base}/es/nosotros",
            "{$base}/es/contacto",
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
     * Si el día de mañana se activa "en" en config('cms.active_locales'),
     * el sitemap debe listar también sus 3 URLs sin que nadie reescriba el
     * controlador -- esto es lo que hace que el mecanismo sea "dinámico" y
     * no una lista cableada.
     */
    public function test_activating_a_new_locale_adds_its_urls_without_touching_the_mechanism(): void
    {
        config(['cms.active_locales' => ['es', 'en']]);

        $response = $this->get('/sitemap.xml');

        $xml = simplexml_load_string($response->getContent());
        $locs = $this->locsFrom($xml);

        $base = rtrim(config('app.url'), '/');

        $this->assertContains("{$base}/en", $locs);
        $this->assertContains("{$base}/en/nosotros", $locs);
        $this->assertContains("{$base}/en/contacto", $locs);
        $this->assertCount(6, $locs);
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
