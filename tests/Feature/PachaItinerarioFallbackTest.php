<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Defecto 2 (auditoria cliente, 2026-09-14): en la ficha en ingles
 * desaparecian por completo el itinerario y "incluye"/"no incluye", aunque
 * el titulo y el resumen SI mostraban su traduccion real. Causa raiz:
 * itinerary/inclusions/exclusions son atributos cast a `array`, y
 * Filament siempre guarda un valor (aunque sea "[]") para CADA pestaña de
 * idioma al guardar el formulario, incluso la que la clienta nunca abrio.
 * Spatie\Translatable\HasTranslations contaba ese "[]" como "SI
 * traducido" y nunca caia al idioma de respaldo -- a diferencia de un
 * string vacio ('' en title/summary), que si se trata como "no
 * traducido". Ver el fix en App\Models\Tour::filterTranslations().
 */
class PachaItinerarioFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function tourWithEnglishTitleButSpanishOnlyLists(): Tour
    {
        return Tour::factory()->create([
            'slug' => ['es' => 'tour-listas-en-espanol', 'en' => 'tour-listas-en-espanol'],
            'title' => ['es' => 'Tour de prueba', 'en' => 'Test tour'],
            'summary' => ['es' => 'Resumen de prueba', 'en' => 'Test summary'],
            'itinerary' => ['es' => [
                ['title' => 'Dia 1', 'description' => 'Llegada.'],
            ], 'en' => []],
            'inclusions' => ['es' => ['Guia'], 'en' => []],
            'exclusions' => ['es' => ['Propinas'], 'en' => []],
        ]);
    }

    public function test_the_model_treats_an_empty_translated_array_as_untranslated(): void
    {
        $tour = $this->tourWithEnglishTitleButSpanishOnlyLists();

        $this->assertSame(['es'], $tour->getTranslatedLocales('itinerary'));
        $this->assertSame(['es'], $tour->getTranslatedLocales('inclusions'));
        $this->assertSame(['es'], $tour->getTranslatedLocales('exclusions'));

        $this->assertTrue($tour->isContentFallbackFor('en', 'itinerary'));
        $this->assertTrue($tour->isContentFallbackFor('en', 'inclusions'));
        $this->assertTrue($tour->isContentFallbackFor('en', 'exclusions'));
        $this->assertFalse($tour->isContentFallbackFor('en', 'title'));
    }

    public function test_a_real_english_translation_is_still_reported_as_translated_not_as_fallback(): void
    {
        $tour = Tour::factory()->create([
            'itinerary' => ['es' => [
                ['title' => 'Dia 1', 'description' => 'Llegada.'],
            ], 'en' => [
                ['title' => 'Day 1', 'description' => 'Arrival.'],
            ]],
        ]);

        $this->assertSame(['es', 'en'], $tour->getTranslatedLocales('itinerary'));
        $this->assertFalse($tour->isContentFallbackFor('en', 'itinerary'));
        $this->assertSame(
            [['title' => 'Day 1', 'description' => 'Arrival.']],
            $tour->getTranslation('itinerary', 'en', false)
        );
    }

    public function test_the_english_ficha_shows_the_spanish_itinerary_and_inclusions_with_a_fallback_notice_instead_of_hiding_them(): void
    {
        $this->tourWithEnglishTitleButSpanishOnlyLists();

        $response = $this->get('/en/tours/tour-listas-en-espanol');

        $response->assertOk();
        $response->assertSee('Test tour', false);
        $response->assertSee('Dia 1', false);
        $response->assertSee('Llegada.', false);
        $response->assertSee('Guia', false);
        $response->assertSee('Propinas', false);
        $response->assertSee('role="note"', false);
        $response->assertSee('lang="es"', false);
    }

    public function test_a_tour_with_no_list_content_in_any_locale_still_hides_the_sections(): void
    {
        Tour::factory()->create([
            'slug' => ['es' => 'tour-sin-listas'],
            'itinerary' => null,
            'inclusions' => null,
            'exclusions' => null,
        ]);

        $response = $this->get('/es/tours/tour-sin-listas');

        $response->assertOk();
        $response->assertDontSee('Itinerario');
    }
}
