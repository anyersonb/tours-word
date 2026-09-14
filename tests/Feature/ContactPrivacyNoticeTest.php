<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * DEF-B (docs/lote-3/validacion-visual-2026-09-14.md, punto 5). El guardián
 * de patrones vive en Tests\Unit\PublicCopyHasNoInternalNotesTest; acá se
 * comprueba lo que de verdad llega al navegador en las dos situaciones
 * posibles de la casilla de privacidad, porque el texto se armaba
 * CONCATENANDO tres claves y el problema no se veía leyendo una sola.
 *
 * La regla que se está fijando: sin documento publicado, la página ni enlaza
 * una política que no existe ni afirma que exista — y tampoco cuenta por qué
 * no existe.
 */
class ContactPrivacyNoticeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function activeLocales(): array
    {
        return [
            'espanol' => ['es'],
            'ingles' => ['en'],
        ];
    }

    #[DataProvider('activeLocales')]
    public function test_without_a_published_policy_the_checkbox_says_what_the_data_is_used_for(string $locale): void
    {
        // Sin la clave, __() devuelve su propio nombre y assertSee(__(...))
        // pasaría comparando basura contra basura: el chequeo tiene que
        // reventar si alguien borra la traducción, no aprobar en silencio.
        $this->assertTrue(
            Lang::has('site.contacto.form.privacy_no_policy', $locale),
            "Falta site.contacto.form.privacy_no_policy en {$locale}."
        );

        // Sin Setting: es el estado real del sitio hoy.
        $this->assertNull(Setting::get('privacy_policy_url'));

        $response = $this->get("/{$locale}/contacto");

        $response->assertOk();

        // No se nombra una política que no está publicada...
        $response->assertDontSee('política de privacidad');
        $response->assertDontSee('privacy policy');

        // ...ni se enlaza a ninguna parte desde la casilla.
        $response->assertDontSee('>política de privacidad</a>', escape: false);
        $response->assertDontSee('>privacy policy</a>', escape: false);

        // Lo que sí se dice: para qué se usan los datos.
        $response->assertSee(__('site.contacto.form.privacy_no_policy', [], $locale));

        // Y la casilla sigue existiendo y siendo obligatoria (Ley 29733).
        $response->assertSee('name="privacy"', escape: false);
    }

    #[DataProvider('activeLocales')]
    public function test_with_a_published_policy_the_checkbox_links_to_it(string $locale): void
    {
        Setting::set('privacy_policy_url', 'https://pachaviva.test/politica-de-privacidad', 'string', 'legal');

        $response = $this->get("/{$locale}/contacto");

        $response->assertOk();
        $response->assertSee('https://pachaviva.test/politica-de-privacidad', escape: false);
        $response->assertSee(__('site.contacto.form.privacy_link', [], $locale));

        // Y entonces la frase suelta NO aparece: son dos redacciones
        // excluyentes, nunca las dos a la vez.
        $response->assertDontSee(__('site.contacto.form.privacy_no_policy', [], $locale));
    }

    /**
     * Las cuatro superficies públicas donde la clienta encontró (o podría
     * volver a encontrar) una nota nuestra. Se comprueban sobre el HTML
     * servido, no sobre el archivo de idioma: una clave puede estar limpia y
     * la vista seguir imprimiendo texto suelto.
     *
     * @return array<string, array{0: string}>
     */
    public static function publicPages(): array
    {
        return [
            'portada ES' => ['/es/'],
            'portada EN' => ['/en/'],
            'contacto ES' => ['/es/contacto'],
            'contacto EN' => ['/en/contacto'],
            'nosotros ES' => ['/es/nosotros'],
            'nosotros EN' => ['/en/nosotros'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_no_public_page_mentions_our_contracting_party_or_our_process(string $url): void
    {
        $response = $this->get($url);

        $response->assertOk();

        $html = $response->getContent();

        $this->assertNotEmpty($html, "La respuesta de {$url} vino vacía: el chequeo no probaría nada.");

        foreach ([
            'la clienta',
            'el cliente debe',
            'the client',
            'en preparación',
            'being drafted',
            'sin envío real',
            'no real submission',
        ] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase(
                $forbidden,
                $html,
                "{$url} publica una nota interna: \"{$forbidden}\""
            );
        }
    }
}
