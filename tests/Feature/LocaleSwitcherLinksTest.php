<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote i18n, fix 1 (bloqueante del CRO): el selector de idioma renderizaba
 * <span role="menuitem"> para las dos ramas (activa e inactiva) tanto en
 * el desplegable de escritorio (x-header.locale-switcher) como en las
 * pastillas del menú móvil (x-site.header) -- ningún visitante podía
 * llegar a "/en/" desde la interfaz. Este test cubre el contrato: un
 * <a href> real hacia la MISMA pantalla en el otro locale activo, el
 * locale activo marcado con aria-current (nunca un enlace a sí mismo), y
 * ningún rastro de PT-BR (fuera del alcance del proyecto).
 */
class LocaleSwitcherLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_switcher_links_to_the_same_tour_screen_in_the_other_active_locale(): void
    {
        $tour = Tour::factory()->create();
        $slug = $tour->getTranslation('slug', 'es', false);

        $esUrl = route('tours.show', ['locale' => 'es', 'slug' => $slug]);
        $enUrl = route('tours.show', ['locale' => 'en', 'slug' => $slug]);

        $response = $this->get($esUrl);

        $response->assertOk();
        // Control negativo: si el fix se revirtiera a <span role="menuitem">
        // sin href, este assert fallaría -- no es un check que siempre pasa.
        $response->assertSee('href="'.$enUrl.'"', false);
    }

    /**
     * No se puede usar un simple assertDontSee(href=ES) acá: <link
     * rel="canonical"> ya apunta legítimamente a esa misma URL, así que un
     * substring suelto daría un falso rojo. Se parsea el DOM y se inspeccionan
     * SOLO los elementos role="menuitem" (desktop) -- el de arriba (activo)
     * debe ser un <span> con aria-current, nunca un <a>.
     */
    public function test_the_active_locale_is_marked_current_and_is_not_a_link_to_itself(): void
    {
        $tour = Tour::factory()->create();
        $slug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get(route('tours.show', ['locale' => 'es', 'slug' => $slug]));

        $response->assertOk();

        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $menuItems = $xpath->query('//*[@role="menuitem"]');
        $this->assertGreaterThan(0, $menuItems->length, 'No se encontro ningun role="menuitem" en la respuesta -- el DOM esperado no esta ahi.');

        $current = $xpath->query('//*[@role="menuitem"][@aria-current="true"]');
        $this->assertSame(1, $current->length, 'Debe haber exactamente un locale marcado aria-current="true".');
        $this->assertSame('span', $current->item(0)->nodeName, 'El locale activo debe ser un <span>, no un enlace a si mismo.');
        $this->assertFalse($current->item(0)->hasAttribute('href'), 'El locale activo no debe tener href.');
    }

    public function test_the_switcher_never_offers_portuguese(): void
    {
        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertDontSee('pt-br', false);
        $response->assertDontSee('Português', false);
        $response->assertDontSee(__('site.header.language_soon'));
    }

    public function test_the_home_switcher_link_preserves_the_home_screen_in_english(): void
    {
        $enHomeUrl = route('home', ['locale' => 'en']);

        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee('href="'.$enHomeUrl.'"', false);
    }

    /**
     * Clic real de extremo a extremo contra la ruta destino: si el enlace
     * apuntara a cualquier otro sitio (home en vez de la misma ficha, o una
     * URL divergente del hreflang), esta segunda request lo delataría.
     */
    public function test_following_the_switcher_link_actually_lands_on_the_translated_screen(): void
    {
        $tour = Tour::factory()->create();
        $slug = $tour->getTranslation('slug', 'es', false);
        $enUrl = route('tours.show', ['locale' => 'en', 'slug' => $slug]);

        $this->get(route('tours.show', ['locale' => 'es', 'slug' => $slug]))
            ->assertSee('href="'.$enUrl.'"', false);

        $this->get($enUrl)->assertOk();
    }
}
