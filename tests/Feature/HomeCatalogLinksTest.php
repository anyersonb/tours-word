<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote i18n, fix 2 (alto del CRO): las tarjetas de tours/destinos/
 * experiencias destacados en la home llevaban href="#" a pesar de que los
 * modelos reales (con su slug) ya existían. Cubre las dos ramas del bloque
 * de tours destacados (>=2 usa el carrusel, 1 usa la tarjeta suelta) y
 * confirma, en ES y EN, que no queda ningún href="#" en la home.
 */
class HomeCatalogLinksTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): void
    {
        $tour = Tour::factory()->create(['is_featured' => true]);
        $experience = Experience::factory()->create();
        $tour->experiences()->attach($experience);

        Destination::factory()->create();
    }

    public function test_the_home_has_no_placeholder_links_in_spanish(): void
    {
        $this->seedCatalog();

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        // Control negativo: si alguien reintrodujera href="#" en cualquiera
        // de las 3 secciones, este assert lo detecta.
        $response->assertDontSee('href="#"', false);
    }

    public function test_the_home_has_no_placeholder_links_in_english(): void
    {
        $this->seedCatalog();

        $response = $this->get(route('home', ['locale' => 'en']));

        $response->assertOk();
        $response->assertDontSee('href="#"', false);
    }

    public function test_the_featured_tour_card_links_to_its_real_tour_page(): void
    {
        $tour = Tour::factory()->create(['is_featured' => true]);
        $slug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee('href="'.route('tours.show', ['locale' => 'es', 'slug' => $slug]).'"', false);
    }

    public function test_the_destination_and_experience_cards_link_to_their_real_pages(): void
    {
        $destination = Destination::factory()->create();
        $experience = Experience::factory()->create();

        $destinationSlug = $destination->getTranslation('slug', 'es', false);
        $experienceSlug = $experience->getTranslation('slug', 'es', false);

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee('href="'.route('destinations.show', ['locale' => 'es', 'slug' => $destinationSlug]).'"', false);
        $response->assertSee('href="'.route('experiences.show', ['locale' => 'es', 'slug' => $experienceSlug]).'"', false);
    }

    /**
     * Con 2+ tours destacados la vista cambia de tarjeta suelta a
     * x-ui.carousel-shell (rama distinta del @if($featuredTours->count() >=
     * 2) en home.blade.php): el href="#" original vivía en las DOS ramas,
     * cada una con su propio foreach/first(), así que esta rama necesita su
     * propio test.
     */
    public function test_the_carousel_branch_links_every_card_to_its_real_tour_page_when_there_are_two_or_more_featured_tours(): void
    {
        $tours = Tour::factory()->count(2)->create(['is_featured' => true]);

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertDontSee('href="#"', false);

        foreach ($tours as $tour) {
            $slug = $tour->getTranslation('slug', 'es', false);
            $response->assertSee('href="'.route('tours.show', ['locale' => 'es', 'slug' => $slug]).'"', false);
        }
    }
}
