<?php

namespace Tests\Feature;

use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Models\Destination;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Write side of hueco #1 (itinerary): closes the rule "ningun campo que el
 * front lee puede quedar sin un sitio donde la clienta lo escriba" for the
 * Repeater added to App\Filament\Resources\Tours\Schemas\TourForm.
 */
class TourItineraryPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_a_tour_itinerary_can_be_created_from_the_panel(): void
    {
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();

        Livewire::test(CreateTour::class)
            ->fillForm([
                'destination_id' => $destination->id,
                'title' => ['es' => 'Tour con itinerario'],
                'slug' => ['es' => 'tour-con-itinerario-panel'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
                'is_published' => true,
                'itinerary' => [
                    'es' => [
                        ['title' => 'Dia 1: Llegada', 'description' => 'Recojo en el aeropuerto y traslado al hotel.'],
                        ['title' => 'Dia 2: Excursion', 'description' => 'Visita guiada al sitio arqueologico.'],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::query()->where('slug->es', 'tour-con-itinerario-panel')->first();

        $this->assertNotNull($tour);
        // Defecto 1 (auditoria cliente, 2026-09-14): el campo "description"
        // del itinerario paso de Textarea a RichEditor -- Filament pasa el
        // valor entrante por un esquema Tiptap (RichEditorStateCast) que
        // envuelve cada parrafo en <p>...</p>, aunque el texto no tenga
        // ningun formato. Antes de este cambio se guardaba el string tal
        // cual; ahora se guarda como HTML valido (ver RichTextSanitizer,
        // que es lo que sanea esto al renderizarlo en el sitio publico).
        $this->assertSame([
            ['title' => 'Dia 1: Llegada', 'description' => '<p>Recojo en el aeropuerto y traslado al hotel.</p>'],
            ['title' => 'Dia 2: Excursion', 'description' => '<p>Visita guiada al sitio arqueologico.</p>'],
        ], $tour->getTranslation('itinerary', 'es', false));
    }

    public function test_a_tour_can_be_created_with_no_itinerary_steps_at_all(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Tour sin itinerario'],
                'slug' => ['es' => 'tour-sin-itinerario-panel'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::query()->where('slug->es', 'tour-sin-itinerario-panel')->first();

        $this->assertNotNull($tour);
        // Defecto 2 (auditoria cliente, 2026-09-14): Tour::filterTranslations()
        // ahora trata un array vacio como "no traducido" (ver ese metodo) --
        // una consecuencia correcta de esa misma regla es que consultar la
        // traduccion SIN fallback para un locale cuyo unico valor es "[]"
        // ya no encuentra ese locale como traducido, y Spatie devuelve su
        // propio valor por defecto de "nada" ('' en vez de null). El
        // resultado sigue significando lo mismo -- ningun itinerario -- asi
        // que blank() es la comparacion correcta, no un array vacio literal.
        $this->assertTrue(blank($tour->getTranslation('itinerary', 'es', false)));
    }

    /**
     * Defecto 1 (auditoria cliente, 2026-09-14): la descripcion de cada dia
     * del itinerario se convirtio de Textarea a RichEditor (mismo criterio
     * y misma toolbar restringida que Tour::description, ver TourForm) --
     * es contenido narrativo puro sin ningun rol de respaldo de meta
     * description, asi que no hay razon para tratarlo distinto. Cubre las
     * dos direcciones: el formato de la clienta se ve, y un <script>
     * escrito directo en el campo (fillForm, sin pasar por el editor JS)
     * nunca se ejecuta en la ficha publica.
     */
    public function test_an_itinerary_days_formatting_is_rendered_as_html_not_as_escaped_tags(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Tour con itinerario formateado'],
                'slug' => ['es' => 'tour-itinerario-formateado'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
                'is_published' => true,
                'itinerary' => [
                    'es' => [
                        ['title' => 'Dia 1: Llegada', 'description' => '<p>Recojo en el <strong>aeropuerto</strong>.</p>'],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $response = $this->get('/es/tours/tour-itinerario-formateado');

        $response->assertOk();
        $response->assertSee('<p>Recojo en el <strong>aeropuerto</strong>.</p>', false);
        $response->assertDontSee('&lt;p&gt;', false);
        $response->assertDontSee('&lt;strong&gt;', false);
    }

    public function test_a_dangerous_element_in_an_itinerary_day_saved_directly_through_the_panel_never_reaches_the_public_page(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Tour con itinerario con xss'],
                'slug' => ['es' => 'tour-itinerario-con-xss'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
                'is_published' => true,
                'itinerary' => [
                    'es' => [
                        ['title' => 'Dia 1', 'description' => '<p>Hola</p><script>window.pwned=1</script><img src="x" onerror="window.pwned=2">'],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $response = $this->get('/es/tours/tour-itinerario-con-xss');

        $response->assertOk();
        $response->assertSee('Hola', false);
        // No "assertDontSee('<script', false)" generico: la propia pagina
        // trae el bundle de Vite en un <script type="module">, asi que ese
        // check nunca podria fallar de verdad (ver
        // feedback_checks_que_no_pueden_fallar en memoria del agente). Lo
        // que SI prueba que el payload no sobrevivio es que ni el codigo
        // (window.pwned) ni el vector (onerror) aparecen en la pagina.
        $response->assertDontSee('onerror', false);
        $response->assertDontSee('window.pwned', false);
    }
}
