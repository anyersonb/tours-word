<?php

namespace Tests\Feature;

use App\Support\Locale;
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
     * "pt_BR" SÍ está en el esquema (config('cms.locales')) pero no en
     * config('cms.active_locales') -- lote i18n (2026-09-14) activó "en",
     * así que "pt_BR" es ahora el único locale del esquema que sigue
     * inactivo, y quien prueba este caso. Decisión documentada: 404, no
     * redirección a /es/ -- redirigir simularía que /pt-br/ ya existe.
     */
    public function test_a_schema_locale_not_yet_active_is_a_404_not_a_redirect(): void
    {
        $this->assertContains('pt_BR', array_keys(config('cms.locales')));
        $this->assertNotContains('pt_BR', config('cms.active_locales'));

        $response = $this->get('/pt-br/');

        $response->assertNotFound();
        $response->assertHeaderMissing('Location');
    }

    public function test_old_unprefixed_urls_no_longer_resolve(): void
    {
        $this->get('/nosotros')->assertNotFound();
        $this->get('/contacto')->assertNotFound();
    }

    /**
     * Objetivo 5 (lote i18n, 2026-09-14): "/ES/nosotros" respondía 200
     * porque Locale::fromSegment() ya normaliza a minúsculas antes de
     * comparar -- misma familia de fallo que un duplicado www/no-www (ver
     * feedback_www_csp_assets_bloqueados en otro proyecto de la casa). Debe
     * ser 301 a la URL canónica en minúsculas, nunca 200 con el mismo
     * contenido servido dos veces.
     */
    public function test_an_uppercase_locale_segment_redirects_permanently_to_the_lowercase_url(): void
    {
        $response = $this->get('/ES/nosotros');

        $response->assertStatus(301);
        $response->assertRedirect('/es/nosotros');
    }

    /**
     * El 301 debe preservar el resto del path y el query string tal cual
     * -- no basta con mandar siempre a la home del locale corregido.
     */
    public function test_the_uppercase_redirect_preserves_the_rest_of_the_path_and_query_string(): void
    {
        $response = $this->get('/EN/tours?destino=cusco');

        $response->assertStatus(301);
        $response->assertRedirect('/en/tours?destino=cusco');
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
        $this->assertSame('pt_BR', Locale::fromSegment('pt-br'));
        $this->assertNull(Locale::fromSegment('pt_BR'));
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
