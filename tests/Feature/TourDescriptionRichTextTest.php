<?php

namespace Tests\Feature;

use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defecto 1 (auditoria cliente, 2026-09-14): la clienta escribio un parrafo
 * normal en el panel (Tour::description se edita con Filament\Forms\
 * Components\RichEditor -- HTML real) y en la ficha publica veia
 * literalmente "&lt;p&gt;Vive el trekking..." -- la BD guarda HTML, la
 * vista lo escapaba con {{ }}.
 *
 * Decision (ver App\Support\Html\RichTextSanitizer): description SI es un
 * campo con formato -- el panel ya ofrece un editor de texto enriquecido y
 * ya habia contenido real guardado con el. El fix es renderizar ese HTML,
 * saneado, no quitarle el editor a la clienta. Cubre las dos direcciones
 * que pide el encargo: el formato de la clienta se ve como ella lo
 * escribio, y un intento de un elemento peligroso escrito DIRECTO en el
 * campo (fillForm, sin pasar por el editor JS -- simula una cuenta de
 * panel comprometida o un dato manipulado a mano) nunca queda expuesto en
 * el sitio publico.
 */
class TourDescriptionRichTextTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_the_clients_formatting_is_rendered_as_html_not_as_escaped_tags(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Tour con descripcion formateada'],
                'slug' => ['es' => 'tour-descripcion-formateada'],
                'description' => ['es' => '<p>Vive el <strong>trekking</strong> mas clasico de Sudamerica.</p>'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $response = $this->get('/es/tours/tour-descripcion-formateada');

        $response->assertOk();
        // El HTML real, sin escapar -- no "&lt;p&gt;", no "&lt;strong&gt;".
        $response->assertSee('<p>Vive el <strong>trekking</strong> mas clasico de Sudamerica.</p>', false);
        $response->assertDontSee('&lt;p&gt;', false);
        $response->assertDontSee('&lt;strong&gt;', false);
    }

    /**
     * Filament's own RichEditor ya hace pasar el HTML entrante por un
     * esquema Tiptap (ver vendor/filament/forms/src/Components/
     * RichEditor.php, comentario de seguridad al inicio del archivo): un
     * elemento fuera de la toolbar habilitada no sobrevive ni siquiera el
     * guardado. Aun asi, RichTextSanitizer es la segunda capa (defensa en
     * profundidad) para cualquier HTML que llegue a la columna por otra
     * via (import directo, API, un downgrade futuro de Filament) -- este
     * test verifica el resultado final que el visitante ve, sin asumir
     * cual de las dos capas lo detuvo.
     */
    public function test_a_dangerous_element_saved_directly_through_the_panel_never_reaches_the_public_page(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Tour con intento de xss'],
                'slug' => ['es' => 'tour-intento-xss'],
                'description' => ['es' => '<p>Hola</p><script>window.pwned=1</script><img src="x" onerror="window.pwned=2">'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::query()->where('slug->es', 'tour-intento-xss')->first();
        $this->assertNotNull($tour);

        $response = $this->get('/es/tours/tour-intento-xss');

        $response->assertOk();
        $response->assertSee('Hola', false);
        $response->assertDontSee('window.pwned', false);
        $response->assertDontSee('onerror', false);
    }

    /**
     * La toolbar del RichEditor se restringio deliberadamente (ver
     * TourForm) a lo que RichTextSanitizer permite -- una tabla (fuera de
     * esa lista) no puede aparecer en el HTML guardado a traves del panel
     * real; este test confirma que la vista publica la descarta en vez de
     * mostrarla cruda, y que el texto seguro alrededor sobrevive.
     */
    public function test_an_element_outside_the_sanitizer_allowlist_is_stripped_but_the_safe_text_around_it_survives(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Tour con tabla no permitida'],
                'slug' => ['es' => 'tour-tabla-no-permitida'],
                'description' => ['es' => '<p>Antes</p><table><tr><td>fuera de la lista</td></tr></table><p>Despues</p>'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $response = $this->get('/es/tours/tour-tabla-no-permitida');

        $response->assertOk();
        $response->assertSee('<p>Antes</p>', false);
        $response->assertSee('<p>Despues</p>', false);
        $response->assertDontSee('<table', false);
    }
}
