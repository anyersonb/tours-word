<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Support\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Defecto latente de idioma (docs/lote-3/seguridad-2026-09-14.md, V-3,
 * hallado de paso por el auditor, no es superficie de ataque). The
 * catalog controllers received the raw URL SEGMENT as $locale
 * ("es", "en", and — the day it's active — "pt-br") and used it directly
 * as the JSON column key (`slug->{$locale}`) and as
 * tour_slug_histories.locale. For "es"/"en" the segment and the internal
 * locale key happen to be identical strings, which is exactly why this
 * went undetected: "/pt-br/tours/<slug>" would have queried
 * `slug->pt-br` when the schema's key is `slug->pt_BR`
 * (config('cms.locales')), always missing.
 *
 * Fixed at the root (not by deleting Portuguese from the routing scheme):
 * every controller now converts the URL segment to the internal locale via
 * App\Support\Locale::fromSegment() — the project's single, already
 * existing segment<->key conversion point — before touching any JSON
 * column or the tour_slug_histories.locale column. Chosen over "delete
 * pt_BR from the routing scheme" because the schema (config('cms.locales'),
 * the translatable JSON columns) already supports it and
 * LocalePrefixRoutingTest already asserts pt_BR's segment shape
 * ("pt-br", hyphen) — removing it would mean re-adding the same
 * groundwork later instead of a one-line fix now.
 */
class LocaleSegmentToInternalKeyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma la premisa del bug directamente, sin pasar por HTTP: el
     * segmento de portugués y su clave interna son cadenas DISTINTAS
     * (guion vs. guion bajo), a diferencia de "es"/"en" donde coinciden
     * por casualidad.
     */
    public function test_the_portuguese_segment_and_its_internal_locale_key_are_different_strings(): void
    {
        $this->assertSame('pt_BR', Locale::fromSegment('pt-br'));
        $this->assertNotSame('pt-br', Locale::fromSegment('pt-br'));

        // Control negativo: para "es"/"en" SÍ coinciden -- por eso el bug
        // quedó inerte y sin detectar hasta que un auditor lo leyó.
        $this->assertSame('es', Locale::fromSegment('es'));
        $this->assertSame('en', Locale::fromSegment('en'));
    }

    /**
     * Activa "pt_BR" solo para este test (LocalePrefixRoutingTest hace lo
     * mismo con "en") y prueba el camino real: un tour con slug en
     * portugués debe resolver por "/pt-br/tours/<slug>", no dar 404 por
     * buscar "slug->pt-br" en vez de "slug->pt_BR".
     */
    public function test_a_tour_with_a_portuguese_slug_resolves_when_portuguese_is_active(): void
    {
        config(['cms.active_locales' => ['es', 'pt_BR']]);

        $tour = Tour::factory()->create([
            'title' => ['es' => 'Camino Inca', 'pt_BR' => 'Caminho Inca'],
            'slug' => ['es' => 'camino-inca', 'pt_BR' => 'caminho-inca'],
        ]);

        $response = $this->get('/pt-br/tours/caminho-inca');

        $response->assertOk();
        $response->assertSee('Caminho Inca');
    }

    public function test_a_destination_with_a_portuguese_slug_resolves_when_portuguese_is_active(): void
    {
        config(['cms.active_locales' => ['es', 'pt_BR']]);

        Destination::factory()->create([
            'name' => ['es' => 'Cusco', 'pt_BR' => 'Cusco PT'],
            'slug' => ['es' => 'cusco', 'pt_BR' => 'cusco-pt'],
        ]);

        $response = $this->get('/pt-br/destinos/cusco-pt');

        $response->assertOk();
    }

    public function test_an_experience_with_a_portuguese_slug_resolves_when_portuguese_is_active(): void
    {
        config(['cms.active_locales' => ['es', 'pt_BR']]);

        Experience::factory()->create([
            'name' => ['es' => 'Trekking', 'pt_BR' => 'Trekking PT'],
            'slug' => ['es' => 'trekking', 'pt_BR' => 'trekking-pt'],
        ]);

        $response = $this->get('/pt-br/experiencias/trekking-pt');

        $response->assertOk();
    }

    /**
     * El filtro del catálogo (?destino=/?experiencia=) también construye
     * "slug->{$locale}" -- mismo defecto, mismo fix.
     */
    public function test_the_tour_catalog_filter_by_destination_works_with_a_portuguese_slug(): void
    {
        config(['cms.active_locales' => ['es', 'pt_BR']]);

        $destination = Destination::factory()->create([
            'slug' => ['es' => 'cusco', 'pt_BR' => 'cusco-pt'],
        ]);
        Tour::factory()->create([
            'destination_id' => $destination->id,
            'is_published' => true,
        ]);

        $response = $this->get('/pt-br/tours?destino=cusco-pt');

        $response->assertOk();
    }
}
