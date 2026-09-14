<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fix 4 (auditoria SEO, lote SEO): datos estructurados JSON-LD.
 * BreadcrumbList en las fichas de destino/experiencia (x-seo.breadcrumb-jsonld)
 * y FAQPage en Contacto (x-seo.faq-jsonld). Organization/LocalBusiness/
 * TravelAgency NO se implementan -- el NAP (dirección, teléfono, correo,
 * RUC, RNAVT) está vacío en Setting hoy, y publicar un esquema con datos en
 * blanco o inventados es peor que no tenerlo (bloqueado por la clienta).
 */
class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, mixed>
     */
    private function firstJsonLdBlock(string $html): array
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $this->assertNotEmpty($matches, 'debe existir al menos un bloque <script type="application/ld+json"> en la página');

        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded, 'el bloque JSON-LD debe ser JSON válido');

        return $decoded;
    }

    public function test_a_destination_show_page_includes_a_valid_breadcrumblist_schema(): void
    {
        Destination::factory()->create(['slug' => ['es' => 'destino-schema'], 'name' => ['es' => 'Destino Schema']]);

        $response = $this->get('/es/destinos/destino-schema');
        $response->assertOk();

        $schema = $this->firstJsonLdBlock($response->getContent());

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('BreadcrumbList', $schema['@type']);
        $this->assertCount(3, $schema['itemListElement']);
        $this->assertSame('Destino Schema', $schema['itemListElement'][2]['name']);
        // El ultimo item (pagina actual) no lleva "item" -- es la convencion
        // que ya usa x-ui.breadcrumbs (ver $breadcrumbItems en la vista).
        $this->assertArrayNotHasKey('item', $schema['itemListElement'][2]);
        $this->assertArrayHasKey('item', $schema['itemListElement'][0]);
    }

    public function test_an_experience_show_page_includes_a_valid_breadcrumblist_schema(): void
    {
        Experience::factory()->create(['slug' => ['es' => 'experiencia-schema'], 'name' => ['es' => 'Experiencia Schema']]);

        $response = $this->get('/es/experiencias/experiencia-schema');
        $response->assertOk();

        $schema = $this->firstJsonLdBlock($response->getContent());

        $this->assertSame('BreadcrumbList', $schema['@type']);
        $this->assertSame('Experiencia Schema', $schema['itemListElement'][2]['name']);
    }

    public function test_the_contact_page_includes_a_valid_faqpage_schema_with_the_five_real_questions(): void
    {
        $response = $this->get('/es/contacto');
        $response->assertOk();

        $schema = $this->firstJsonLdBlock($response->getContent());

        $this->assertSame('FAQPage', $schema['@type']);
        $this->assertCount(5, $schema['mainEntity']);

        foreach ($schema['mainEntity'] as $question) {
            $this->assertSame('Question', $question['@type']);
            $this->assertNotEmpty($question['name']);
            $this->assertSame('Answer', $question['acceptedAnswer']['@type']);
            $this->assertNotEmpty($question['acceptedAnswer']['text']);
        }

        // Ninguna pregunta/respuesta es contenido inventado -- viene 1:1 de
        // site.contacto.faq.items (ES), la misma fuente que ya renderiza
        // x-ui.faq-item en la seccion visible de la pagina.
        $this->assertSame(
            __('site.contacto.faq.items.0.question'),
            $schema['mainEntity'][0]['name']
        );
    }

    /**
     * El test mas importante del fix 4 (mandato explicito del lote): un
     * nombre de destino que trae comillas, un cierre de <script> y un
     * <img onerror> nunca puede romper fuera del bloque JSON-LD. En otro
     * proyecto de la casa un JSON-LD mal escapado abrio un XSS real.
     */
    public function test_a_malicious_destination_name_cannot_break_out_of_the_jsonld_script_block(): void
    {
        $maliciousName = '</script><img src=x onerror="alert(1)">"\'&';

        Destination::factory()->create([
            'slug' => ['es' => 'destino-malicioso'],
            'name' => ['es' => $maliciousName],
        ]);

        $response = $this->get('/es/destinos/destino-malicioso');
        $response->assertOk();

        $html = $response->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $this->assertNotEmpty($matches, 'debe existir el bloque JSON-LD');

        $jsonRaw = $matches[1];

        // El contenido crudo del bloque NUNCA puede contener un "</script"
        // literal -- eso cerraria el <script> antes de tiempo y el resto
        // ("<img src=x onerror=...>") se interpretaria como HTML/JS fuera
        // del contexto de datos, exactamente el XSS que ya paso una vez.
        $this->assertStringNotContainsString('</script', $jsonRaw);
        $this->assertStringNotContainsString('<img', $jsonRaw);

        // Y sigue siendo JSON valido que decodifica AL MISMO string
        // original -- el escapado no corrompe el dato, solo lo hace seguro
        // de imprimir dentro de un <script>.
        $decoded = json_decode($jsonRaw, true);
        $this->assertNotNull($decoded, 'el bloque JSON-LD debe seguir siendo JSON valido tras el escapado');
        $this->assertSame($maliciousName, $decoded['itemListElement'][2]['name']);
    }

    /**
     * Control negativo del control anterior: confirma que el assert de
     * arriba SI puede fallar (no es un check que nunca falla, ver
     * feedback_checks_que_no_pueden_fallar en la memoria del equipo).
     *
     * json_encode() por defecto YA escapa "/" a "\/", lo que de rebote
     * tambien rompe la subcadena literal "</script" -- por eso este control
     * usa JSON_UNESCAPED_SLASHES (una bandera real que alguien podria
     * agregar por "URLs mas limpias" sin pensar en el riesgo) para
     * demostrar el escenario que SI deja pasar el payload cuando falta
     * JSON_HEX_TAG. x-seo.breadcrumb-jsonld nunca usa
     * JSON_UNESCAPED_SLASHES -- esto no es lo que corre en produccion.
     */
    public function test_json_encode_without_the_hex_flags_would_have_let_the_payload_through(): void
    {
        $maliciousName = '</script><img src=x onerror="alert(1)">';

        $unsafe = json_encode(['name' => $maliciousName], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('</script', $unsafe, 'este assert demuestra que el payload SI rompe sin JSON_HEX_TAG -- el fix real esta en x-seo.breadcrumb-jsonld');
    }
}
