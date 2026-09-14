<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote i18n (2026-09-14): activación de "en" en config('cms.active_locales')
 * (objetivo 4) y fallback honesto del contenido de catálogo (objetivo 2).
 * El resto de los objetivos del lote (1, 3, 5, 6) tienen su propia
 * cobertura: ver LangKeyParityTest (guardián de claves) y
 * LocalePrefixRoutingTest (301 de mayúsculas).
 */
class EnglishActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_nine_public_screens_respond_ok_in_english(): void
    {
        $tour = Tour::factory()->create();
        $experience = Experience::factory()->create();
        $tour->experiences()->attach($experience);

        $tourSlug = $tour->getTranslation('slug', 'es', false);
        $destinationSlug = $tour->destination->getTranslation('slug', 'es', false);
        $experienceSlug = $experience->getTranslation('slug', 'es', false);

        $this->get('/en')->assertOk();
        $this->get('/en/tours')->assertOk();
        $this->get("/en/tours/{$tourSlug}")->assertOk();
        $this->get('/en/destinos')->assertOk();
        $this->get("/en/destinos/{$destinationSlug}")->assertOk();
        $this->get('/en/experiencias')->assertOk();
        $this->get("/en/experiencias/{$experienceSlug}")->assertOk();
        $this->get('/en/nosotros')->assertOk();
        $this->get('/en/contacto')->assertOk();
    }

    /**
     * hreflang solo se emite en páginas indexables (home/nosotros/contacto
     * -- los 6 screens de catálogo son noindex mientras el contenido sea de
     * MUESTRA, ver resources/views/components/layout.blade.php). Cada
     * alternate debe ser una URL real que responde 200, nunca una que
     * redirige o da 404 -- si no, sería peor que no declarar hreflang.
     */
    public function test_hreflang_is_reciprocal_between_spanish_and_english_with_x_default(): void
    {
        $cases = ['home', 'about', 'contact'];

        foreach ($cases as $routeName) {
            $esUrl = route($routeName, ['locale' => 'es']);
            $enUrl = route($routeName, ['locale' => 'en']);

            $es = $this->get($esUrl);
            $en = $this->get($enUrl);

            $es->assertOk();
            $en->assertOk();

            foreach ([$es, $en] as $response) {
                $body = $response->getContent();

                $this->assertStringContainsString('hreflang="es" href="'.$esUrl.'"', $body);
                $this->assertStringContainsString('hreflang="en" href="'.$enUrl.'"', $body);
                $this->assertStringContainsString('hreflang="x-default" href="'.$esUrl.'"', $body);
            }
        }
    }

    /**
     * Objetivo 2: un tour que SOLO tiene traducción al español (el caso real
     * del día uno) debe resolver bajo "/en/" -- nunca 404 -- y su contenido
     * nunca puede quedar en blanco: cae al español, y lo dice honestamente
     * en el HTML (lang="es" en el bloque de contenido + aviso visible),
     * nunca finge que el texto está en inglés.
     */
    public function test_a_tour_without_english_translation_still_resolves_under_english_and_never_shows_blank_fields(): void
    {
        $tour = Tour::factory()->create();
        $slug = $tour->getTranslation('slug', 'es', false);
        $title = $tour->getTranslation('title', 'es', false);

        $this->assertNotContains('en', $tour->getTranslatedLocales('title'), 'este fixture no debe tener traduccion en ingles: si la tuviera, el test no probaria el fallback');

        $response = $this->get("/en/tours/{$slug}");

        $response->assertOk();
        // Nunca en blanco: el título en español aparece igual bajo /en/.
        $response->assertSee($title, false);
        // Honesto: el bloque de contenido declara lang="es", no "en".
        $response->assertSee('lang="es"', false);
        // Substring sin el apostrofe: Blade escapa "isn't" a "isn&#039;t" en
        // el HTML servido, así que comparar el string crudo de __() contra
        // el body no matchea aunque el aviso SI se haya renderizado.
        $response->assertSee('showing the original version', false);
        $response->assertSee('role="note"', false);
    }

    /**
     * Control: el mismo tour bajo "/es/" (su idioma real) NO debe mostrar
     * el aviso de fallback ni marcar nada como lang="es" a mano -- eso solo
     * aplica cuando el contenido está sirviéndose en un locale que no es el
     * suyo.
     */
    public function test_the_same_tour_shows_no_fallback_notice_when_viewed_in_its_own_language(): void
    {
        $tour = Tour::factory()->create();
        $slug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get("/es/tours/{$slug}");

        $response->assertOk();
        $response->assertDontSee('role="note"', false);
    }

    /**
     * Mismo fallback que el tour, para destino y experiencia -- confirma
     * que ResolvesBySlugByLocale no es un caso especial de Tour.
     */
    public function test_a_destination_and_an_experience_without_english_translation_also_resolve_under_english(): void
    {
        $destination = Destination::factory()->create();
        $experience = Experience::factory()->create();

        $destinationSlug = $destination->getTranslation('slug', 'es', false);
        $experienceSlug = $experience->getTranslation('slug', 'es', false);

        $this->get("/en/destinos/{$destinationSlug}")
            ->assertOk()
            ->assertSee($destination->getTranslation('name', 'es', false), false)
            ->assertSee('lang="es"', false);

        $this->get("/en/experiencias/{$experienceSlug}")
            ->assertOk()
            ->assertSee($experience->getTranslation('name', 'es', false), false)
            ->assertSee('lang="es"', false);
    }
}
