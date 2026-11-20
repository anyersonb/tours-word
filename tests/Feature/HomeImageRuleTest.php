<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regla del Home (docs/rediseno-2026/21-ojo-home-vs-pdf.md): un destino o tour
 * SIN imagen no entra al Home; la composición se completa con los del diseño
 * en el orden del PDF. Y los Planes siempre salen en 4 tarjetas.
 */
class HomeImageRuleTest extends TestCase
{
    use RefreshDatabase;

    private function home(): string
    {
        return $this->get(route('home', ['locale' => 'es']))->assertOk()->getContent();
    }

    private function bentoNames(string $html): array
    {
        $start = strpos($html, 'class="mosaic"');
        $end = strpos($html, 'id="tours"', $start);
        preg_match_all('/dest__name">([^<]+)</', substr($html, $start, $end - $start), $m);

        return $m[1];
    }

    public function test_destination_without_image_is_not_in_the_bento_and_design_ones_fill_in_pdf_order(): void
    {
        Destination::factory()->create(['name' => ['es' => 'Arequipa'], 'slug' => ['es' => 'arequipa']]);
        Destination::factory()->create(['name' => ['es' => 'Cusco'], 'slug' => ['es' => 'cusco']]);

        $this->assertSame(
            ['Machu Picchu', 'Cusco', 'Valle Sagrado', 'Ica', 'Humantay'],
            $this->bentoNames($this->home()),
        );
    }

    public function test_empty_cms_renders_the_five_design_destinations(): void
    {
        $this->assertSame(
            ['Machu Picchu', 'Cusco', 'Valle Sagrado', 'Ica', 'Humantay'],
            $this->bentoNames($this->home()),
        );
    }

    public function test_featured_tour_without_image_is_replaced_by_the_four_design_cards(): void
    {
        Tour::factory()->sample()->create(['title' => ['es' => 'Camino Inca 4 días'], 'is_featured' => true]);

        $html = $this->home();
        $start = strpos($html, 'id="tours"');
        $block = substr($html, $start, strpos($html, 'id="camino-inca"', $start) - $start);

        preg_match_all('/tour-card__title">([^<]+)</', $block, $m);
        $this->assertSame(['City Tour Lima', 'Laguna Humantay', 'Montaña de 7 Colores', 'Machu Picchu'], $m[1]);
        $this->assertStringContainsString('Últimos cupos', $block);
        $this->assertStringNotContainsString('Camino Inca 4 días', $block);
    }

    public function test_featured_tour_with_image_is_shown_first(): void
    {
        Tour::factory()->withImage()->create(['title' => ['es' => 'Tour Real Zeta'], 'is_featured' => true]);

        $this->assertStringContainsString('Tour Real Zeta', $this->home());
    }

    public function test_plans_always_render_four_cards_even_with_imageless_cms_tours_in_the_buckets(): void
    {
        // Tours demo del CMS sin foto que antes reservaban el grupo de duración.
        Tour::factory()->sample()->create(['title' => ['es' => 'Camino Inca 4 días'], 'duration_label' => ['es' => '4 días / 3 noches']]);
        Tour::factory()->sample()->create(['title' => ['es' => 'Tour gastronómico'], 'duration_label' => ['es' => '4 horas']]);

        $html = $this->home();

        $this->assertSame(4, substr_count($html, '<article class="plan"'));
        preg_match_all('/plan__title">([^<]+)</', $html, $m);
        $this->assertSame(['City Tour Lima', 'Montaña de 7 Colores', 'Ica y Nazca', 'Camino Inca a Machu Picchu, 4 días'], $m[1]);
    }

    public function test_real_plan_tour_with_image_takes_its_bucket(): void
    {
        Tour::factory()->withImage()->create(['title' => ['es' => 'Ruta Real Zeta'], 'duration_label' => ['es' => '2 días / 1 noche']]);

        $html = $this->home();

        $this->assertSame(4, substr_count($html, '<article class="plan"'));
        $this->assertStringContainsString('Ruta Real Zeta', $html);
        $this->assertStringNotContainsString('Ica y Nazca', $html);
    }

    public function test_faq_shows_six_questions_without_placeholders_and_testimonials_stay_hidden(): void
    {
        $html = $this->home();

        $this->assertSame(6, substr_count($html, 'class="acc '));
        $this->assertStringNotContainsString('Respuesta por completar', $html);
        $this->assertStringNotContainsString('Lo que dicen nuestros viajeros', $html);
        $this->assertStringContainsString('Viaja con quienes conocen el camino', $html);
        // Una sola cifra suelta en la banda Paisajes no se muestra.
        $this->assertStringNotContainsString('band__stats', $html);
    }

    public function test_city_tour_count_pills_show_in_all_cards_or_in_none(): void
    {
        $cusco = Destination::factory()->create(['name' => ['es' => 'Cusco'], 'slug' => ['es' => 'cusco']]);
        Tour::factory()->create(['destination_id' => $cusco->id]);

        // Solo Cusco tiene conteo >0: para ser consistente, ninguna tarjeta muestra la pill.
        $this->assertStringNotContainsString('count-pill', $this->home());
    }
}
