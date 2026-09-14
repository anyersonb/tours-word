<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote i18n, fix 3 (alto del CRO): tours/show.blade.php usaba $tour['title']
 * y $tour['summary'] para el <title>/<meta description> aunque Tour ya
 * tiene meta_title/meta_description editables en Filament -- el mismo
 * fallo de "campo del CMS que nadie lee" que ya mordió a este equipo antes.
 * Cubre el caso real (meta_title/meta_description con contenido propio,
 * distinto del título/resumen) y el de respaldo (campo meta vacío).
 */
class TourMetaTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_title_tag_uses_meta_title_when_it_has_its_own_content(): void
    {
        $tour = Tour::factory()->create([
            'title' => ['es' => 'Camino Inca clasico'],
            'meta_title' => ['es' => 'Camino Inca 4 dias | Reserva con guia experto'],
        ]);
        $slug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get(route('tours.show', ['locale' => 'es', 'slug' => $slug]));

        $response->assertOk();
        $response->assertSee('<title>Camino Inca 4 dias | Reserva con guia experto', false);
        // Control negativo: si el fix se revirtiera a $tour['title'], el
        // <title> real (no el <h1>) contendria esto en su lugar.
        $response->assertDontSee('<title>Camino Inca clasico', false);
    }

    public function test_the_description_meta_uses_meta_description_when_it_has_its_own_content(): void
    {
        $tour = Tour::factory()->create([
            'summary' => ['es' => 'Resumen corto del tour.'],
            'meta_description' => ['es' => 'Descripcion SEO distinta del resumen visible.'],
        ]);
        $slug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get(route('tours.show', ['locale' => 'es', 'slug' => $slug]));

        $response->assertOk();
        $response->assertSee('<meta name="description" content="Descripcion SEO distinta del resumen visible."', false);
    }

    public function test_the_title_falls_back_to_the_tour_title_when_meta_title_is_empty(): void
    {
        $tour = Tour::factory()->create([
            'title' => ['es' => 'Tour Valle Sagrado completo'],
            'meta_title' => ['es' => ''],
        ]);
        $slug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get(route('tours.show', ['locale' => 'es', 'slug' => $slug]));

        $response->assertOk();
        $response->assertSee('<title>Tour Valle Sagrado completo', false);
        // Nunca un <title> vacio: el fallback debe imprimir el nombre de la
        // marca a continuacion del titulo, no quedarse en blanco.
        $response->assertDontSee('<title></title>', false);
        $response->assertDontSee('<title> ·', false);
    }

    public function test_the_description_falls_back_to_the_tour_summary_when_meta_description_is_empty(): void
    {
        $tour = Tour::factory()->create([
            'summary' => ['es' => 'Resumen visible usado como respaldo.'],
            'meta_description' => ['es' => ''],
        ]);
        $slug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get(route('tours.show', ['locale' => 'es', 'slug' => $slug]));

        $response->assertOk();
        $response->assertSee('<meta name="description" content="Resumen visible usado como respaldo."', false);
    }
}
