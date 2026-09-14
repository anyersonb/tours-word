<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
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
        config(['cms.catalog_demo_content' => false]);

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
        config(['cms.catalog_demo_content' => false]);

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
        config(['cms.catalog_demo_content' => false]);

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
