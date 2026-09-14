<?php

namespace Tests\Feature;

use App\Mail\NewContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * DEF-A (docs/lote-3/validacion-visual-2026-09-14.md, punto 4): "Reservar
 * este tour" apuntaba a /es/contacto a secas — sin el tour, sin ancla al
 * formulario y sin asunto preseleccionado. El correo que llegaba a la
 * agencia no decía de qué tour se trataba.
 *
 * Los tours se construyen con slugs DISTINTOS en ES y EN a propósito: con el
 * mismo slug en los dos idiomas (lo que hace TourFactory por defecto, que
 * solo rellena "es") un enlace que armara la URL con el idioma equivocado
 * pasaría igual y el test no probaría nada — ver el precedente de DEF-01/
 * DEF-02 en este mismo repo.
 */
class ContactBookingRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function bilingualTour(array $overrides = []): Tour
    {
        return Tour::factory()->create(array_merge([
            'title' => ['es' => 'Camino a Choquequirao', 'en' => 'Choquequirao Trek'],
            'slug' => ['es' => 'camino-a-choquequirao', 'en' => 'choquequirao-trek'],
            'summary' => ['es' => 'Resumen', 'en' => 'Summary'],
            'description' => ['es' => '<p>Texto</p>', 'en' => '<p>Text</p>'],
            'is_published' => true,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rosa Mamani',
            'email' => 'rosa@example.com',
            'phone' => '+51 999 888 777',
            'subject' => 'reserva',
            'message' => 'Quisiera reservar para dos personas en mayo.',
            'privacy' => 'on',
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // 1. El enlace sale de la ficha llevando el tour y el ancla
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function localesAndSlugs(): array
    {
        return [
            'espanol' => ['es', 'camino-a-choquequirao'],
            'ingles' => ['en', 'choquequirao-trek'],
        ];
    }

    #[DataProvider('localesAndSlugs')]
    public function test_the_tour_page_cta_carries_the_tour_and_an_anchor_to_the_form(string $locale, string $slug): void
    {
        $this->bilingualTour();

        $response = $this->get("/{$locale}/tours/{$slug}");

        $response->assertOk();

        // El atributo href ENTERO, no un trozo: si el CTA volviera a
        // route('contact') a secas, o perdiera el ancla, o llevara el slug
        // del otro idioma, esta aserción falla. (No se comprueba "que NO
        // exista /contacto a secas" porque el pie de página y el menú sí lo
        // enlazan legítimamente: ese check pasaría siempre y no probaría
        // nada.)
        $response->assertSee(
            'href="'.url("/{$locale}/contacto").'?tour='.$slug.'#formulario"',
            escape: false
        );

        // La otra mitad del desajuste de promesa: el botón ya no dice
        // "Reservar" a secas y explica qué pasa al pulsarlo.
        $response->assertSee(__('site.tours.show.cta_reserve', [], $locale));
        $response->assertSee(__('site.tours.show.cta_reserve_hint', [], $locale));
    }

    public function test_the_contact_page_exposes_the_anchor_the_cta_points_at(): void
    {
        $this->get('/es/contacto')->assertSee('id="formulario"', escape: false);
    }

    // ------------------------------------------------------------------
    // 2. La página de contacto entiende de qué tour viene
    // ------------------------------------------------------------------

    #[DataProvider('localesAndSlugs')]
    public function test_arriving_from_a_tour_preselects_the_subject_and_shows_the_tour(string $locale, string $slug): void
    {
        $tour = $this->bilingualTour();
        $expectedTitle = $tour->getTranslation('title', $locale);

        $response = $this->get("/{$locale}/contacto?tour={$slug}");

        $response->assertOk();

        // Lo ve el visitante: el título del tour, en su idioma.
        $response->assertSee($expectedTitle, escape: false);

        // El asunto llega puesto en "Reserva de un tour".
        $response->assertSee('<option value="reserva" selected>', escape: false);

        // Y viaja en el envío.
        $response->assertSee('<input type="hidden" name="tour" value="'.$slug.'">', escape: false);
    }

    public function test_the_generic_contact_page_preselects_nothing_and_shows_no_tour(): void
    {
        $this->bilingualTour();

        $response = $this->get('/es/contacto');

        $response->assertOk();
        $response->assertDontSee('<option value="reserva" selected>', escape: false);
        $response->assertDontSee('<input type="hidden" name="tour"', escape: false);
        $response->assertDontSee('Camino a Choquequirao');
    }

    // ------------------------------------------------------------------
    // 3. Identificador inválido, inexistente o de un tour despublicado
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function unusableTourQueryStrings(): array
    {
        return [
            'slug inexistente' => ['tour=no-existe-este-tour'],
            'slug vacio' => ['tour='],
            'array' => ['tour[]=camino-a-choquequirao'],
            'array anidado' => ['tour[a][b]=camino-a-choquequirao'],
            'array numerado' => ['tour[0]=camino-a-choquequirao'],
            'con etiquetas' => ['tour=%3Cscript%3Ealert(1)%3C/script%3E'],
            'travesia de rutas' => ['tour=../../etc/passwd'],
            'demasiado largo' => ['tour=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ];
    }

    #[DataProvider('unusableTourQueryStrings')]
    public function test_an_unusable_tour_identifier_never_breaks_the_page_and_is_never_printed(string $queryString): void
    {
        $this->bilingualTour();

        $response = $this->get("/es/contacto?{$queryString}");

        // Ni 500 ni 404: la página de contacto sigue sirviendo su formulario.
        $response->assertOk();
        $response->assertDontSee('Array to string conversion');

        // Nada de lo que vino en la URL se imprime de vuelta.
        $response->assertDontSee('no-existe-este-tour');
        $response->assertDontSee('alert(1)', escape: false);
        $response->assertDontSee('etc/passwd');

        // Y el formulario queda genérico: sin bloque de tour, sin asunto
        // preseleccionado y sin campo oculto que arrastre basura al correo.
        $response->assertDontSee('<input type="hidden" name="tour"', escape: false);
        $response->assertDontSee('<option value="reserva" selected>', escape: false);
    }

    public function test_an_unpublished_tour_is_treated_as_if_it_did_not_exist(): void
    {
        $this->bilingualTour(['is_published' => false]);

        $response = $this->get('/es/contacto?tour=camino-a-choquequirao');

        $response->assertOk();
        $response->assertDontSee('Camino a Choquequirao');
        $response->assertDontSee('<input type="hidden" name="tour"', escape: false);
    }

    public function test_a_tour_title_with_markup_is_escaped_on_the_contact_page(): void
    {
        // El título sale del CMS: es una superficie de contenido de la
        // clienta, no de la URL, pero llega a la misma pantalla que el
        // parámetro y tiene que salir escapado igual.
        $this->bilingualTour([
            'title' => ['es' => 'Tour <script>alert(1)</script>', 'en' => 'Tour EN'],
        ]);

        $response = $this->get('/es/contacto?tour=camino-a-choquequirao');

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', escape: false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false);
    }

    // ------------------------------------------------------------------
    // 4. El tour llega al buzón y al correo de la agencia
    // ------------------------------------------------------------------

    public function test_a_submission_from_a_tour_stores_the_tour_and_names_it_in_the_email(): void
    {
        Mail::fake();

        $tour = $this->bilingualTour();

        $response = $this->from('/es/contacto?tour=camino-a-choquequirao')
            ->post('/es/contacto', $this->validPayload(['tour' => 'camino-a-choquequirao']));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('contact_success', true);

        $message = ContactMessage::query()->firstOrFail();
        $this->assertSame($tour->id, $message->tour_id);
        $this->assertSame('Camino a Choquequirao', $message->tour_title);

        Mail::assertSent(NewContactMessageReceived::class, function (NewContactMessageReceived $mail): bool {
            // Lo que la clienta ve sin abrir el mensaje.
            $this->assertStringContainsString('Camino a Choquequirao', $mail->build()->subject);

            // Y dentro del cuerpo.
            $this->assertStringContainsString('Camino a Choquequirao', $mail->render());

            return true;
        });
    }

    public function test_the_english_submission_stores_the_title_the_visitor_actually_read(): void
    {
        Mail::fake();

        $this->bilingualTour();

        $this->post('/en/contacto', $this->validPayload(['tour' => 'choquequirao-trek']))
            ->assertSessionHasNoErrors();

        $message = ContactMessage::query()->firstOrFail();
        $this->assertSame('Choquequirao Trek', $message->tour_title);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unusableTourFields(): array
    {
        return [
            'inexistente' => ['no-existe-este-tour'],
            'array' => [['camino-a-choquequirao']],
            'con etiquetas' => ['<script>alert(1)</script>'],
            'demasiado largo' => ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ];
    }

    #[DataProvider('unusableTourFields')]
    public function test_a_tampered_tour_field_never_costs_the_agency_the_lead(mixed $tourField): void
    {
        Mail::fake();

        $this->bilingualTour();

        $response = $this->post('/es/contacto', $this->validPayload(['tour' => $tourField]));

        // El mensaje es un cliente real: se guarda igual, nunca se rebota con
        // un error de validación por un enlace profundo que no resolvió.
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contact_messages', 1);

        $message = ContactMessage::query()->firstOrFail();
        $this->assertNull($message->tour_id);
        $this->assertNull($message->tour_title);
    }

    public function test_the_generic_submission_keeps_working_untouched(): void
    {
        Mail::fake();

        $this->post('/es/contacto', $this->validPayload(['subject' => 'consulta']))
            ->assertSessionHasNoErrors();

        $message = ContactMessage::query()->firstOrFail();
        $this->assertNull($message->tour_id);
        $this->assertNull($message->tour_title);

        Mail::assertSent(NewContactMessageReceived::class, function (NewContactMessageReceived $mail): bool {
            $this->assertStringContainsString('Nuevo mensaje de contacto', $mail->build()->subject);

            return true;
        });
    }

    public function test_deleting_a_tour_never_deletes_the_lead_and_never_erases_which_tour_it_was(): void
    {
        Mail::fake();

        $tour = $this->bilingualTour();

        $this->post('/es/contacto', $this->validPayload(['tour' => 'camino-a-choquequirao']));

        $tour->delete();

        $message = ContactMessage::query()->firstOrFail()->fresh();
        $this->assertNull($message->tour_id);
        $this->assertSame('Camino a Choquequirao', $message->tour_title);
    }
}
