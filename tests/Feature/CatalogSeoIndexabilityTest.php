<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fix 6 (auditoria SEO, lote SEO): "indexacion del ingles" -- decision ya
 * tomada. Una pieza de catalogo cuyo contenido cae al locale de respaldo en
 * la URL pedida va noindex, sin su alterno hreflang hacia ese locale, y
 * fuera del sitemap; una pieza con traduccion real es indexable, con su
 * alterno.
 *
 * config('cms.catalog_demo_content') se apaga (false) SOLO en estos tests:
 * en produccion sigue en true (DemoTourSeeder), asi que hoy TODAS las
 * fichas de catalogo son noindex sin importar la traduccion -- ver el
 * comentario de esa clave en config/cms.php. Sin apagarla, este mecanismo
 * de fallback-por-locale nunca podria probarse en aislamiento (siempre
 * daria noindex=true por la otra razon).
 */
class CatalogSeoIndexabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_destination_with_a_real_english_translation_is_indexable_under_english_with_reciprocal_hreflang(): void
    {
        // is_staging_mirror declarado explícito (M-1, config/cms.php): desde
        // que la bandera falla CERRADA, "no declararla" ya no equivale a
        // false -- este test simula contenido real publicado en el dominio
        // propio de Pacha Viva, así que necesita la declaración explícita.
        config(['cms.catalog_demo_content' => false, 'cms.is_staging_mirror' => false]);

        Destination::factory()->create([
            'slug' => ['es' => 'cusco-bilingue', 'en' => 'cusco-bilingual'],
            'name' => ['es' => 'Cusco', 'en' => 'Cusco'],
            'description' => ['es' => 'Descripción en español.', 'en' => 'Description in English.'],
        ]);

        $esUrl = route('destinations.show', ['locale' => 'es', 'slug' => 'cusco-bilingue']);
        $enUrl = route('destinations.show', ['locale' => 'en', 'slug' => 'cusco-bilingual']);

        $es = $this->get($esUrl);
        $en = $this->get($enUrl);

        $es->assertOk();
        $en->assertOk();

        foreach ([$es, $en] as $response) {
            $response->assertDontSee('noindex', false);
            $response->assertSee('hreflang="es" href="'.$esUrl.'"', false);
            $response->assertSee('hreflang="en" href="'.$enUrl.'"', false);
        }
    }

    public function test_a_destination_without_english_translation_is_noindex_with_no_english_alternate_and_stays_out_of_the_sitemap(): void
    {
        // is_staging_mirror declarado explícito (M-1, config/cms.php): desde
        // que la bandera falla CERRADA, "no declararla" ya no equivale a
        // false -- este test simula contenido real publicado en el dominio
        // propio de Pacha Viva, así que necesita la declaración explícita.
        config(['cms.catalog_demo_content' => false, 'cms.is_staging_mirror' => false]);

        Destination::factory()->create([
            'slug' => ['es' => 'cusco-solo-espanol'],
            'name' => ['es' => 'Cusco Solo Español'],
        ]);

        // La version que cae a fallback (en) es noindex y no declara NINGUN
        // hreflang -- ver components/layout.blade.php, @unless($noindex).
        $en = $this->get('/en/destinos/cusco-solo-espanol');
        $en->assertOk();
        $en->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $en->assertDontSee('hreflang=', false);

        // La version indexable (es) tampoco debe ofrecer el alterno hacia
        // "en": esa URL seria noindex, y un hreflang hacia una URL noindex
        // es una contradiccion (mandato del lote).
        $es = $this->get('/es/destinos/cusco-solo-espanol');
        $es->assertOk();
        $es->assertDontSee('noindex', false);
        $es->assertDontSee('hreflang="en"', false);

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertOk();
        $sitemap->assertDontSee('/destinos/cusco-solo-espanol', false);
    }

    public function test_an_experience_without_english_translation_is_noindex_with_no_english_alternate(): void
    {
        // is_staging_mirror declarado explícito (M-1, config/cms.php): desde
        // que la bandera falla CERRADA, "no declararla" ya no equivale a
        // false -- este test simula contenido real publicado en el dominio
        // propio de Pacha Viva, así que necesita la declaración explícita.
        config(['cms.catalog_demo_content' => false, 'cms.is_staging_mirror' => false]);

        Experience::factory()->create([
            'slug' => ['es' => 'trekking-solo-espanol'],
            'name' => ['es' => 'Trekking Solo Español'],
        ]);

        $en = $this->get('/en/experiencias/trekking-solo-espanol');
        $en->assertOk();
        $en->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $es = $this->get('/es/experiencias/trekking-solo-espanol');
        $es->assertOk();
        $es->assertDontSee('hreflang="en"', false);
    }

    /**
     * Defecto 1 (cierre lote SEO, 2026-09-14): la ficha de tour quedo atras
     * del resto del catalogo -- no tenia hreflang. Mismo control que el
     * equivalente de destino de arriba, y ademas el regresivo real del bug
     * que este mecanismo destapo: "slug" es columna traducible (distinto en
     * cada locale), asi que sin hreflangUrls por-slug el alterno "en"
     * hubiera reusado el slug ESPAÑOL de la request actual (ver
     * feedback_hreflang_slug_por_locale en la memoria del equipo).
     */
    public function test_a_tour_with_a_real_english_translation_is_indexable_under_english_with_reciprocal_hreflang(): void
    {
        // is_staging_mirror declarado explícito (M-1, config/cms.php): desde
        // que la bandera falla CERRADA, "no declararla" ya no equivale a
        // false -- este test simula contenido real publicado en el dominio
        // propio de Pacha Viva, así que necesita la declaración explícita.
        config(['cms.catalog_demo_content' => false, 'cms.is_staging_mirror' => false]);

        Tour::factory()->create([
            'slug' => ['es' => 'camino-inca-bilingue', 'en' => 'inca-trail-bilingual'],
            'title' => ['es' => 'Camino Inca', 'en' => 'Inca Trail'],
            'summary' => ['es' => 'Resumen en español.', 'en' => 'Summary in English.'],
            'description' => ['es' => 'Descripción en español.', 'en' => 'Description in English.'],
        ]);

        $esUrl = route('tours.show', ['locale' => 'es', 'slug' => 'camino-inca-bilingue']);
        $enUrl = route('tours.show', ['locale' => 'en', 'slug' => 'inca-trail-bilingual']);

        $es = $this->get($esUrl);
        $en = $this->get($enUrl);

        $es->assertOk();
        $en->assertOk();

        foreach ([$es, $en] as $response) {
            $response->assertDontSee('noindex', false);
            $response->assertSee('hreflang="es" href="'.$esUrl.'"', false);
            $response->assertSee('hreflang="en" href="'.$enUrl.'"', false);
            // Regresion directa del bug: el alterno "en" NUNCA debe apuntar
            // al slug español reutilizado.
            $response->assertDontSee('hreflang="en" href="'.route('tours.show', ['locale' => 'en', 'slug' => 'camino-inca-bilingue']).'"', false);
        }
    }

    public function test_a_tour_without_english_translation_is_noindex_with_no_english_alternate(): void
    {
        // is_staging_mirror declarado explícito (M-1, config/cms.php): desde
        // que la bandera falla CERRADA, "no declararla" ya no equivale a
        // false -- este test simula contenido real publicado en el dominio
        // propio de Pacha Viva, así que necesita la declaración explícita.
        config(['cms.catalog_demo_content' => false, 'cms.is_staging_mirror' => false]);

        Tour::factory()->create([
            'slug' => ['es' => 'camino-inca-solo-espanol'],
            'title' => ['es' => 'Camino Inca Solo Español'],
        ]);

        $en = $this->get('/en/tours/camino-inca-solo-espanol');
        $en->assertOk();
        $en->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $en->assertDontSee('hreflang=', false);

        $es = $this->get('/es/tours/camino-inca-solo-espanol');
        $es->assertOk();
        $es->assertDontSee('noindex', false);
        $es->assertDontSee('hreflang="en"', false);
    }

    /**
     * Control: mismo mandato de "las dos razones se combinan con OR" que ya
     * cubre Destination/Experience, ahora tambien para Tour.
     */
    public function test_a_fully_translated_tour_is_still_noindex_while_the_catalog_is_demo_content(): void
    {
        $this->assertTrue(config('cms.catalog_demo_content'), 'este test depende del valor por defecto (produccion); si cambio, revisar el resto de la suite');

        Tour::factory()->create([
            'slug' => ['es' => 'camino-inca-bilingue-pero-muestra', 'en' => 'inca-trail-bilingual-but-demo'],
            'title' => ['es' => 'Camino Inca', 'en' => 'Inca Trail'],
        ]);

        $response = $this->get('/en/tours/inca-trail-bilingual-but-demo');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    /**
     * Control: mientras config('cms.catalog_demo_content') siga en true
     * (valor real de hoy, DemoTourSeeder), la ficha es noindex SIEMPRE, sin
     * importar que tenga traduccion real -- las dos razones se combinan con
     * OR, nunca se reemplazan entre si.
     */
    public function test_a_fully_translated_destination_is_still_noindex_while_the_catalog_is_demo_content(): void
    {
        $this->assertTrue(config('cms.catalog_demo_content'), 'este test depende del valor por defecto (produccion); si cambio, revisar el resto de la suite');

        Destination::factory()->create([
            'slug' => ['es' => 'cusco-bilingue-pero-muestra', 'en' => 'cusco-bilingual-but-demo'],
            'name' => ['es' => 'Cusco', 'en' => 'Cusco'],
        ]);

        $response = $this->get('/en/destinos/cusco-bilingual-but-demo');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
