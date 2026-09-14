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

    /**
     * Defecto 1 (auditoria CRO, ALTO): sin respaldo, x-ui.gallery renderiza
     * un <img> con src="" (caja 960x540 en blanco) cuando el destino no
     * tiene fotos todavia -- caso real del dia uno de la clienta. El
     * respaldo es el mismo marcador de posicion SVG local que ya usa
     * tours/show.blade.php para el mismo caso (data URI, sin red).
     */
    public function test_a_destination_with_no_gallery_images_falls_back_to_the_placeholder_image(): void
    {
        Destination::factory()->create(['slug' => ['es' => 'destino-sin-galeria-2']]);

        $response = $this->get('/es/destinos/destino-sin-galeria-2');

        $response->assertOk();
        $response->assertSee('data:image/svg+xml;base64,', false);
        // Control negativo: nunca un <img src=""> vacio.
        $response->assertDontSee('src=""', false);
    }

    public function test_an_experience_with_no_gallery_images_falls_back_to_the_placeholder_image(): void
    {
        Experience::factory()->create(['slug' => ['es' => 'experiencia-sin-galeria']]);

        $response = $this->get('/es/experiencias/experiencia-sin-galeria');

        $response->assertOk();
        $response->assertSee('data:image/svg+xml;base64,', false);
        $response->assertDontSee('src=""', false);
    }

    /**
     * Defecto 3 (auditoria SEO, ALTO): Cusco/Trekking no tenian
     * meta_title/meta_description dedicados y "description" venia vacio ->
     * <meta name="description"> salia vacio (confirmado por curl antes del
     * fix). Sin meta_title/meta_description en Filament, la ficha debe
     * caer honestamente al nombre (title) y quedarse sin description
     * cuando tampoco hay "description" -- nunca un <title> vacio, y
     * x-layout cae a su respaldo de marca para "description" en ese caso.
     */
    public function test_a_destination_without_seo_metadata_falls_back_to_its_name_for_the_title_tag(): void
    {
        Destination::factory()->create([
            'slug' => ['es' => 'destino-sin-seo'],
            'name' => ['es' => 'Cusco Sin Meta'],
            'description' => ['es' => null],
        ]);

        $response = $this->get('/es/destinos/destino-sin-seo');

        $response->assertOk();
        $response->assertSee('<title>Cusco Sin Meta · '.config('app.name').'</title>', false);
        // Nunca vacio: cae al respaldo de marca de x-layout, no a "".
        $response->assertDontSee('<meta name="description" content="">', false);
    }

    /**
     * Fix 2 (cierre lote SEO, 2026-09-14): un meta_title escrito por la
     * clienta ya trae su propia marca -- se usa literal, sin el sufijo
     * " · {app.name}" que x-layout compone para el titulo de RESPALDO
     * (nombre del destino). Antes del fix este <title> salia duplicado:
     * "... | Meta SEO · Pacha Viva".
     */
    public function test_a_destination_with_seo_metadata_uses_it_literally_without_appending_the_site_name(): void
    {
        Destination::factory()->create([
            'slug' => ['es' => 'destino-con-seo'],
            'name' => ['es' => 'Cusco'],
            'description' => ['es' => 'Descripción visible en la página.'],
            'meta_title' => ['es' => 'Cusco: tours y experiencias | Meta SEO'],
            'meta_description' => ['es' => 'Meta descripción dedicada para buscadores.'],
        ]);

        $response = $this->get('/es/destinos/destino-con-seo');

        $response->assertOk();
        $response->assertSee('<title>Cusco: tours y experiencias | Meta SEO</title>', false);
        $response->assertDontSee('Meta SEO · '.config('app.name'), false);
        $response->assertSee('<meta name="description" content="Meta descripción dedicada para buscadores.">', false);
    }

    /**
     * Fix 2 (cierre lote SEO): mismo control que la destinacion de arriba,
     * para la experiencia -- meta_title escrito por la clienta literal, sin
     * el sufijo " · {app.name}" encima.
     */
    public function test_an_experience_with_seo_metadata_uses_it_literally_without_appending_the_site_name(): void
    {
        Experience::factory()->create([
            'slug' => ['es' => 'experiencia-con-seo'],
            'name' => ['es' => 'Trekking'],
            'description' => ['es' => 'Descripción visible en la página.'],
            'meta_title' => ['es' => 'Trekking en los Andes | Meta SEO'],
            'meta_description' => ['es' => 'Meta descripción dedicada para buscadores.'],
        ]);

        $response = $this->get('/es/experiencias/experiencia-con-seo');

        $response->assertOk();
        $response->assertSee('<title>Trekking en los Andes | Meta SEO</title>', false);
        $response->assertDontSee('Meta SEO · '.config('app.name'), false);
    }

    /**
     * Defecto 8 (docs/lote-3/seguridad-2026-09-14.md, Bajo, cosmético — no
     * es XSS). `<x-layout title="{{ $metaTitle }}">` escapaba el título
     * (Blade corre e() dentro de {{ }}) y luego lo pasaba como STRING ya
     * escapado al prop "title"; x-layout volvía a escaparlo con otro
     * {{ $pageTitle }} para el <title> real -- un "&" o una comilla salían
     * duplicados ("&amp;amp;"). El fix cambia a :title="$metaTitle" (bind
     * de Blade, sin pasar por {{ }}), así que el escape ocurre una sola
     * vez, en el punto de salida real.
     */
    public function test_a_destination_title_with_special_characters_is_escaped_exactly_once(): void
    {
        Destination::factory()->create([
            'slug' => ['es' => 'destino-caracteres-especiales'],
            'name' => ['es' => 'Cusco'],
            'meta_title' => ['es' => 'Tours & "Cusco" <especial>'],
        ]);

        $response = $this->get('/es/destinos/destino-caracteres-especiales');

        $response->assertOk();
        $response->assertSee('<title>Tours &amp; &quot;Cusco&quot; &lt;especial&gt;</title>', false);
        $response->assertDontSee('&amp;amp;', false);
        $response->assertDontSee('&amp;quot;', false);
    }

    public function test_an_experience_title_with_special_characters_is_escaped_exactly_once(): void
    {
        Experience::factory()->create([
            'slug' => ['es' => 'experiencia-caracteres-especiales'],
            'name' => ['es' => 'Trekking'],
            'meta_title' => ['es' => 'Treks & "Aventura" <especial>'],
        ]);

        $response = $this->get('/es/experiencias/experiencia-caracteres-especiales');

        $response->assertOk();
        $response->assertSee('<title>Treks &amp; &quot;Aventura&quot; &lt;especial&gt;</title>', false);
        $response->assertDontSee('&amp;amp;', false);
        $response->assertDontSee('&amp;quot;', false);
    }

    public function test_an_experience_without_seo_metadata_falls_back_to_its_description_for_the_meta_tag(): void
    {
        Experience::factory()->create([
            'slug' => ['es' => 'experiencia-sin-seo'],
            'name' => ['es' => 'Trekking Sin Meta'],
            'description' => ['es' => 'Descripción de respaldo de la experiencia.'],
        ]);

        $response = $this->get('/es/experiencias/experiencia-sin-seo');

        $response->assertOk();
        $response->assertSee('<meta name="description" content="Descripción de respaldo de la experiencia.">', false);
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
