<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\DestinationImage;
use App\Models\Experience;
use App\Models\ExperienceImage;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Backend real del catalogo/ficha de destinos y experiencias (lote 1,
 * reemplazo de la maqueta del lote 3 -- App\Http\Controllers\
 * {Destination,Experience}Controller).
 */
class DestinationAndExperiencePublicCatalogRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_destinations_index_lists_only_published_destinations(): void
    {
        $published = Destination::factory()->create(['name' => ['es' => 'Cusco Publicado'], 'is_published' => true]);
        Destination::factory()->create(['name' => ['es' => 'Destino Borrador'], 'is_published' => false]);

        $response = $this->get('/es/destinos');

        $response->assertOk();
        $response->assertSee('Cusco Publicado');
        $response->assertDontSee('Destino Borrador');
        $this->assertTrue($published->exists);
    }

    public function test_a_destination_is_shown_by_slug_with_its_related_published_tours(): void
    {
        $destination = Destination::factory()->create(['slug' => ['es' => 'cusco-ficha'], 'name' => ['es' => 'Cusco Ficha'], 'is_published' => true]);
        $relatedTour = Tour::factory()->create(['destination_id' => $destination->id, 'title' => ['es' => 'Tour Relacionado'], 'is_published' => true]);
        $unrelatedTour = Tour::factory()->create(['title' => ['es' => 'Tour de Otro Destino'], 'is_published' => true]);

        $response = $this->get('/es/destinos/cusco-ficha');

        $response->assertOk();
        $response->assertSee('Cusco Ficha');
        $response->assertSee('Tour Relacionado');
        $response->assertDontSee('Tour de Otro Destino');
        $this->assertTrue($relatedTour->exists && $unrelatedTour->exists);
    }

    public function test_an_unknown_destination_slug_returns_404(): void
    {
        $this->get('/es/destinos/no-existe')->assertNotFound();
    }

    public function test_a_destination_with_no_gallery_images_renders_without_errors(): void
    {
        Destination::factory()->create(['slug' => ['es' => 'destino-sin-galeria']]);

        $this->get('/es/destinos/destino-sin-galeria')->assertOk();
    }

    public function test_the_destination_gallery_round_trips_the_translatable_alt_text(): void
    {
        $destination = Destination::factory()->create(['slug' => ['es' => 'destino-con-galeria']]);
        $image = DestinationImage::factory()->for($destination)->create(['alt' => ['es' => 'Foto de portada de prueba']]);

        $this->assertSame('Foto de portada de prueba', $image->getTranslation('alt', 'es'));
        $this->assertSame($destination->id, $image->destination_id);

        $response = $this->get('/es/destinos/destino-con-galeria');
        $response->assertOk();
    }

    public function test_the_experiences_index_lists_only_published_experiences(): void
    {
        $published = Experience::factory()->create(['name' => ['es' => 'Trekking Publicado'], 'is_published' => true]);
        Experience::factory()->create(['name' => ['es' => 'Experiencia Borrador'], 'is_published' => false]);

        $response = $this->get('/es/experiencias');

        $response->assertOk();
        $response->assertSee('Trekking Publicado');
        $response->assertDontSee('Experiencia Borrador');
        $this->assertTrue($published->exists);
    }

    public function test_an_experience_is_shown_by_slug_with_its_related_published_tours(): void
    {
        $experience = Experience::factory()->create(['slug' => ['es' => 'trekking-ficha'], 'name' => ['es' => 'Trekking Ficha'], 'is_published' => true]);
        $relatedTour = Tour::factory()->create(['title' => ['es' => 'Tour Con Trekking'], 'is_published' => true]);
        $relatedTour->experiences()->attach($experience);

        $unrelatedTour = Tour::factory()->create(['title' => ['es' => 'Tour Sin Trekking'], 'is_published' => true]);

        $response = $this->get('/es/experiencias/trekking-ficha');

        $response->assertOk();
        $response->assertSee('Trekking Ficha');
        $response->assertSee('Tour Con Trekking');
        $response->assertDontSee('Tour Sin Trekking');
        $this->assertTrue($unrelatedTour->exists);
    }

    public function test_an_unknown_experience_slug_returns_404(): void
    {
        $this->get('/es/experiencias/no-existe')->assertNotFound();
    }

    public function test_the_experience_gallery_round_trips_the_translatable_alt_text(): void
    {
        $experience = Experience::factory()->create(['slug' => ['es' => 'experiencia-con-galeria']]);
        $image = ExperienceImage::factory()->for($experience)->create(['alt' => ['es' => 'Foto de experiencia de prueba']]);

        $this->assertSame('Foto de experiencia de prueba', $image->getTranslation('alt', 'es'));
        $this->assertSame($experience->id, $image->experience_id);

        $response = $this->get('/es/experiencias/experiencia-con-galeria');
        $response->assertOk();
    }

    /**
     * N+1 guard for the destinations catalog (DestinationController::index
     * eager loads "gallery"): query count must not grow with the number of
     * destinations or images.
     */
    public function test_the_destinations_index_does_not_n_plus_one_as_the_gallery_grows(): void
    {
        $makeDestinationWithImages = function () {
            $destination = Destination::factory()->create(['is_published' => true]);
            DestinationImage::factory()->for($destination)->count(3)->create();

            return $destination;
        };

        $makeDestinationWithImages();

        DB::enableQueryLog();

        $queryCount = function () {
            DB::flushQueryLog();
            $this->get('/es/destinos')->assertOk();

            return count(DB::getQueryLog());
        };

        $countWithOneDestination = $queryCount();

        for ($i = 0; $i < 4; $i++) {
            $makeDestinationWithImages();
        }

        $countWithFiveDestinations = $queryCount();

        $this->assertSame(
            $countWithOneDestination,
            $countWithFiveDestinations,
            'The destinations catalog must not issue extra queries per destination/image (N+1 regression).'
        );
    }
}
