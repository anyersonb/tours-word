<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Models\TourImage;
use App\Models\TourSlugHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Backend real del catalogo/ficha de tours (lote 1, reemplazo de la maqueta
 * del lote 3 -- App\Http\Controllers\TourController). Cubre lo que el
 * contrato del encargo pide explicitamente: resolucion por slug, el 301 del
 * slug historico, el 404 de un slug inexistente, la paginacion, que las dos
 * monedas formatean, y que la ficha carga sin N+1.
 */
class TourPublicCatalogRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_paginates_published_tours_six_per_page(): void
    {
        Tour::factory()->count(8)->create(['is_published' => true]);
        Tour::factory()->create(['is_published' => false]);

        $pageOne = $this->get('/es/tours');
        $pageOne->assertOk();
        $pageOne->assertViewHas('tours', function ($tours) {
            return $tours->count() === 6 && $tours->total() === 8;
        });

        $pageTwo = $this->get('/es/tours?page=2');
        $pageTwo->assertOk();
        $pageTwo->assertViewHas('tours', fn ($tours) => $tours->count() === 2);
    }

    public function test_the_index_filters_by_destination_and_experience_slug(): void
    {
        $cusco = Destination::factory()->create(['slug' => ['es' => 'cusco'], 'is_published' => true]);
        $arequipa = Destination::factory()->create(['slug' => ['es' => 'arequipa'], 'is_published' => true]);
        $trekking = Experience::factory()->create(['slug' => ['es' => 'trekking'], 'is_published' => true]);

        $matching = Tour::factory()->create(['destination_id' => $cusco->id, 'is_published' => true]);
        $matching->experiences()->attach($trekking);

        $otherDestination = Tour::factory()->create(['destination_id' => $arequipa->id, 'is_published' => true]);
        $otherDestination->experiences()->attach($trekking);

        $noExperience = Tour::factory()->create(['destination_id' => $cusco->id, 'is_published' => true]);

        $response = $this->get('/es/tours?destino=cusco&experiencia=trekking');

        $response->assertOk();
        $response->assertViewHas('tours', function ($tours) use ($matching) {
            return $tours->count() === 1 && $tours->first()->is($matching);
        });
    }

    public function test_a_published_tour_is_shown_by_its_current_slug_with_both_currencies_formatted(): void
    {
        $tour = Tour::factory()->create([
            'slug' => ['es' => 'tour-de-prueba-publico'],
            'title' => ['es' => 'Tour de Prueba Publico'],
            'is_published' => true,
            'price_pen_cents' => 15000,
            'price_usd_cents' => 4000,
        ]);

        $response = $this->get('/es/tours/tour-de-prueba-publico');

        $response->assertOk();
        $response->assertSee('Tour de Prueba Publico');
        $response->assertSee('S/ 150.00', false);
        $response->assertSee('US$ 40.00', false);
        $this->assertTrue($tour->exists);
    }

    public function test_an_unpublished_tour_is_not_reachable_by_its_slug(): void
    {
        Tour::factory()->create(['slug' => ['es' => 'tour-borrador'], 'is_published' => false]);

        $this->get('/es/tours/tour-borrador')->assertNotFound();
    }

    public function test_an_unknown_slug_returns_404(): void
    {
        $this->get('/es/tours/este-slug-nunca-existio')->assertNotFound();
    }

    /**
     * The 301 the encargo explicitly asks for: tour_slug_histories exists
     * precisely so a URL that used to work never dead-ends (App\Models\
     * TourSlugHistory's docblock). Exercised through a REAL slug change on
     * the model (Tour::booted()), not a hand-built history row, so this
     * fails if that hook ever stops writing history.
     */
    public function test_a_retired_slug_redirects_301_to_the_current_one(): void
    {
        $tour = Tour::factory()->create(['slug' => ['es' => 'slug-viejo']]);
        $tour->update(['slug' => ['es' => 'slug-nuevo']]);

        $this->assertDatabaseHas('tour_slug_histories', [
            'tour_id' => $tour->id,
            'locale' => 'es',
            'slug' => 'slug-viejo',
        ]);

        $response = $this->get('/es/tours/slug-viejo');

        $response->assertStatus(301);
        $response->assertRedirect('/es/tours/slug-nuevo');
    }

    /**
     * A slug that was retired but whose tour was deleted afterwards must
     * not redirect into a dead record -- it's a 404 like any other unknown
     * slug, not a redirect to nowhere.
     */
    public function test_a_retired_slug_whose_tour_no_longer_exists_returns_404(): void
    {
        $tour = Tour::factory()->create(['slug' => ['es' => 'slug-a-borrar']]);
        $tourId = $tour->id;
        $tour->update(['slug' => ['es' => 'slug-a-borrar-nuevo']]);
        Tour::query()->whereKey($tourId)->delete();

        $this->get('/es/tours/slug-a-borrar')->assertNotFound();
    }

    public function test_a_tour_with_no_images_and_no_itinerary_renders_without_errors(): void
    {
        $tour = Tour::factory()->create([
            'slug' => ['es' => 'tour-sin-galeria-ni-itinerario'],
            'itinerary' => null,
        ]);

        $response = $this->get('/es/tours/tour-sin-galeria-ni-itinerario');

        $response->assertOk();
        // 'Itinerario' is lang/es/site.php's site.tours.show.itinerary_title
        // (hardcoded here, not resolved through __(), so this doesn't
        // depend on the test process's app locale matching the request's).
        $response->assertDontSee('Itinerario');
    }

    public function test_the_itinerary_round_trips_as_a_translatable_structured_array(): void
    {
        $tour = Tour::factory()->create([
            'slug' => ['es' => 'tour-con-itinerario'],
            'itinerary' => ['es' => [
                ['title' => 'Dia 1', 'description' => 'Llegada.'],
                ['title' => 'Dia 2', 'description' => 'Excursion.'],
            ]],
        ]);

        $this->assertSame([
            ['title' => 'Dia 1', 'description' => 'Llegada.'],
            ['title' => 'Dia 2', 'description' => 'Excursion.'],
        ], $tour->getTranslation('itinerary', 'es'));

        $response = $this->get('/es/tours/tour-con-itinerario');

        $response->assertOk();
        $response->assertSee('Dia 1');
        $response->assertSee('Excursion.');
    }

    /**
     * N+1 guard for the catalog: the number of queries the index issues
     * must NOT grow with the number of tours on the page (see
     * TourController::index's eager loading). Regression on the exact
     * mechanism the controller uses (with(['destination', 'experiences',
     * 'images'])), not a generic "it's probably fine" assertion.
     */
    public function test_the_index_does_not_n_plus_one_as_the_tour_count_grows(): void
    {
        $destination = Destination::factory()->create();
        $experience = Experience::factory()->create();

        $makeTourWithRelations = function () use ($destination, $experience) {
            $tour = Tour::factory()->create(['destination_id' => $destination->id, 'is_published' => true]);
            $tour->experiences()->attach($experience);
            TourImage::factory()->for($tour)->create();

            return $tour;
        };

        $makeTourWithRelations();

        DB::enableQueryLog();

        $queryCount = function () {
            DB::flushQueryLog();
            $this->get('/es/tours')->assertOk();

            return count(DB::getQueryLog());
        };

        $countWithOneTour = $queryCount();

        for ($i = 0; $i < 5; $i++) {
            $makeTourWithRelations();
        }

        $countWithSixTours = $queryCount();

        $this->assertSame(
            $countWithOneTour,
            $countWithSixTours,
            'The number of queries the catalog issues must not scale with the number of tours (N+1 regression).'
        );
    }

    /**
     * Same guard for the ficha: query count must not grow with the number
     * of images a single tour has.
     */
    public function test_the_show_page_does_not_n_plus_one_as_the_image_count_grows(): void
    {
        $tourFewImages = Tour::factory()->create(['slug' => ['es' => 'tour-pocas-fotos'], 'is_published' => true]);
        TourImage::factory()->for($tourFewImages)->create();

        $tourManyImages = Tour::factory()->create(['slug' => ['es' => 'tour-muchas-fotos'], 'is_published' => true]);
        TourImage::factory()->for($tourManyImages)->count(6)->create();

        DB::enableQueryLog();

        $queryCount = function (string $slug) {
            DB::flushQueryLog();
            $this->get("/es/tours/{$slug}")->assertOk();

            return count(DB::getQueryLog());
        };

        $this->assertSame(
            $queryCount('tour-pocas-fotos'),
            $queryCount('tour-muchas-fotos'),
            'The ficha must not issue one extra query per image (N+1 regression).'
        );
    }
}
