<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Lote 1, ronda 2 (S-08, docs/lote-1/seo-2026-09-02.md, Bloque 6). Anyerson
 * decidió prefijar TODAS las URLs por locale, incluido español, mientras
 * el sitio no está indexado. Cubre: 301 de "/" a "/es/", 200 en las rutas
 * activas, 404 (no redirección) para un locale del esquema todavía inactivo,
 * 404 para una URL vieja sin prefijo, y que App::setLocale() se llame de
 * verdad (nunca por sesión/cookie/Accept-Language).
 */
class LocalePrefixRoutingTest extends TestCase
{
    public function test_root_redirects_permanently_to_the_spanish_prefix(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/es/');
        $response->assertStatus(301);
    }

    public function test_spanish_prefixed_routes_respond_ok(): void
    {
        $this->get('/es/')->assertOk();
        $this->get('/es/nosotros')->assertOk();
        $this->get('/es/contacto')->assertOk();
    }

    /**
     * Control negativo: si este test pasara con cualquier locale, no
     * probaría nada. "de" no está en config('cms.locales') (el esquema
     * completo), así que debe ser 404, no 200.
     */
    public function test_a_locale_outside_the_schema_is_a_404(): void
    {
        $this->get('/de/')->assertNotFound();
    }

    /**
     * "en" SÍ está en el esquema (config('cms.locales')) pero no en
     * config('cms.active_locales') todavía. Decisión documentada: 404, no
     * redirección a /es/ -- redirigir simularía que /en/ ya existe.
     */
    public function test_a_schema_locale_not_yet_active_is_a_404_not_a_redirect(): void
    {
        $this->assertContains('en', array_keys(config('cms.locales')));
        $this->assertNotContains('en', config('cms.active_locales'));

        $response = $this->get('/en/');

        $response->assertNotFound();
        $response->assertHeaderMissing('Location');
    }

    public function test_old_unprefixed_urls_no_longer_resolve(): void
    {
        $this->get('/nosotros')->assertNotFound();
        $this->get('/contacto')->assertNotFound();
    }

    /**
     * "es" es el fallback de config('app.locale'), así que probar contra
     * "es" no demostraría nada (App::currentLocale() ya sería "es" aunque
     * el middleware no hiciera nada). Se activa "en" solo para este test y
     * se pide "/en/nosotros": si el middleware de verdad llama a
     * App::setLocale() desde el segmento de la URL, currentLocale() cambia
     * a "en"; si el middleware se rompiera (por ejemplo, si alguien lo
     * reemplazara por resolución de sesión/cookie), se quedaría en el
     * fallback "es" y este assert fallaría.
     */
    public function test_the_url_segment_actually_sets_the_application_locale(): void
    {
        config(['cms.active_locales' => ['es', 'en']]);

        $this->assertSame('es', App::currentLocale());

        $this->get('/en/nosotros')->assertOk();

        $this->assertSame('en', App::currentLocale());
    }

    /**
     * "pt-br" en la URL (guion), nunca "pt_BR" (guion bajo), por convención
     * de slugs web -- aunque hoy responda 404 por no estar activo, el
     * segmento debe reconocerse como un locale del esquema, no como uno
     * desconocido.
     */
    public function test_the_url_segment_for_portuguese_uses_a_hyphen_not_an_underscore(): void
    {
        $this->get('/pt-br/')->assertNotFound();
        $this->get('/pt_BR/')->assertNotFound();

        // Ambos son 404 hoy (pt_BR no está activo), pero por razones
        // distintas: "pt-br" es un locale reconocido e inactivo,
        // "pt_BR" (con guion bajo) ni siquiera es un segmento válido.
        $this->assertSame('pt_BR', \App\Support\Locale::fromSegment('pt-br'));
        $this->assertNull(\App\Support\Locale::fromSegment('pt_BR'));
    }

    /**
     * Mandato del lote: los NOMBRES de ruta no cambiaron al agregar el
     * prefijo. Si esto se rompiera, cualquier vista que use route('about')/
     * route('contact')/route('home') fallaría con "route not defined".
     */
    public function test_route_names_are_unchanged_and_resolve_with_the_locale_prefix(): void
    {
        $this->assertTrue(Route::has('home'));
        $this->assertTrue(Route::has('about'));
        $this->assertTrue(Route::has('contact'));
        $this->assertTrue(Route::has('contact.store'));
        $this->assertTrue(Route::has('styleguide'));

        $this->get('/es/nosotros');

        $base = rtrim(config('app.url'), '/');
        $this->assertSame("{$base}/es", route('home'));
        $this->assertSame("{$base}/es/nosotros", route('about'));
        $this->assertSame("{$base}/es/contacto", route('contact'));
    }
}
