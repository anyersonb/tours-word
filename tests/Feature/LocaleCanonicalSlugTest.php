<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * DEF-01 y DEF-02 (docs/lote-3/qa-visual-2026-09-14.md, ambos Altos, la
 * MISMA costura vista por sus dos caras).
 *
 * DEF-01: el selector de idioma armaba la URL del otro idioma cambiando el
 * prefijo de locale y conservando el slug actual. Desde una ficha en inglés,
 * "Español" llevaba a "/es/tours/<slug-en>" -- 404 en los 3 tours con slug
 * propio en inglés.
 *
 * DEF-02: al mismo tiempo, "/en/tours/<slug-es>" respondía 200 con canonical
 * autorreferente, así que cada ficha vivía en dos URLs distintas.
 *
 * Por qué ningún test anterior lo cazó: el proyecto nació con slugs idénticos
 * en los dos idiomas (los slugs ingleses llegaron en 87d8593) y las factories
 * siguen creando registros solo en español. Un caso de prueba con el MISMO
 * slug en ambos idiomas (los reales "cusco", "arequipa", "trekking") pasa en
 * verde con el defecto puesto -- por eso cada caso de aquí usa slugs
 * DISTINTOS por idioma, y hay un caso aparte que fija el comportamiento de
 * los coincidentes para que nadie los confunda con cobertura.
 */
class LocaleCanonicalSlugTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Las tres entidades de catálogo con slug traducible, cada una con slugs
     * DISTINTOS en español e inglés.
     *
     * @return array<string, array{0: string}>
     */
    public static function translatedRecords(): array
    {
        return [
            'tour' => ['tour'],
            'destination' => ['destination'],
            'experience' => ['experience'],
        ];
    }

    /**
     * @return array{0: Model, 1: string, 2: string, 3: string} registro,
     *                                                          nombre de ruta, slug ES, slug EN
     */
    private function makeRecord(string $type): array
    {
        return match ($type) {
            'tour' => [
                Tour::factory()->create([
                    'title' => ['es' => 'Camino de prueba', 'en' => 'Test trail'],
                    'slug' => ['es' => 'camino-de-prueba', 'en' => 'test-trail'],
                ]),
                'tours.show',
                'camino-de-prueba',
                'test-trail',
            ],
            'destination' => [
                Destination::factory()->create([
                    'name' => ['es' => 'Valle de prueba', 'en' => 'Test valley'],
                    'slug' => ['es' => 'valle-de-prueba', 'en' => 'test-valley'],
                ]),
                'destinations.show',
                'valle-de-prueba',
                'test-valley',
            ],
            'experience' => [
                Experience::factory()->create([
                    'name' => ['es' => 'Gastronomía de prueba', 'en' => 'Test cuisine'],
                    'slug' => ['es' => 'gastronomia-de-prueba', 'en' => 'test-cuisine'],
                ]),
                'experiences.show',
                'gastronomia-de-prueba',
                'test-cuisine',
            ],
        };
    }

    /**
     * DEF-02, dirección EN->ES: el slug español bajo el prefijo inglés no es
     * una segunda dirección válida de la ficha, es un duplicado. 301 al
     * canónico inglés, nunca 200.
     */
    #[DataProvider('translatedRecords')]
    public function test_the_spanish_slug_under_the_english_prefix_redirects_to_the_english_canonical(string $type): void
    {
        [, $routeName, $esSlug, $enSlug] = $this->makeRecord($type);

        $this->get(route($routeName, ['locale' => 'en', 'slug' => $esSlug]))
            ->assertRedirect(route($routeName, ['locale' => 'en', 'slug' => $enSlug]))
            ->assertStatus(301);
    }

    /**
     * DEF-01/DEF-02, dirección ES->EN: la cara que hoy da 404 en producción.
     * El slug inglés bajo el prefijo español debe llevar al canónico español,
     * no morir -- es el mismo criterio de tour_slug_histories, que ya
     * funciona.
     */
    #[DataProvider('translatedRecords')]
    public function test_the_english_slug_under_the_spanish_prefix_redirects_to_the_spanish_canonical(string $type): void
    {
        [, $routeName, $esSlug, $enSlug] = $this->makeRecord($type);

        $this->get(route($routeName, ['locale' => 'es', 'slug' => $enSlug]))
            ->assertRedirect(route($routeName, ['locale' => 'es', 'slug' => $esSlug]))
            ->assertStatus(301);
    }

    /**
     * El destino de esos 301 tiene que ser terminal. Si el canónico
     * redirigiera a su vez, el visitante entraría en un bucle y el
     * rastreador abandonaría la URL -- peor que el duplicado que se vino a
     * arreglar.
     */
    #[DataProvider('translatedRecords')]
    public function test_the_canonical_slug_of_each_locale_answers_200_and_does_not_redirect_again(string $type): void
    {
        [, $routeName, $esSlug, $enSlug] = $this->makeRecord($type);

        $this->get(route($routeName, ['locale' => 'es', 'slug' => $esSlug]))->assertOk();
        $this->get(route($routeName, ['locale' => 'en', 'slug' => $enSlug]))->assertOk();
    }

    /**
     * DEF-01, dirección ES->EN: el selector debe declarar el slug INGLÉS, y
     * jamás el español bajo el prefijo inglés (esa era la URL rota).
     */
    #[DataProvider('translatedRecords')]
    public function test_the_switcher_on_the_spanish_page_points_at_the_english_slug(string $type): void
    {
        [, $routeName, $esSlug, $enSlug] = $this->makeRecord($type);

        $correctUrl = route($routeName, ['locale' => 'en', 'slug' => $enSlug]);
        $brokenUrl = route($routeName, ['locale' => 'en', 'slug' => $esSlug]);

        $response = $this->get(route($routeName, ['locale' => 'es', 'slug' => $esSlug]));

        $response->assertOk();
        $response->assertSee('href="'.$correctUrl.'"', false);
        // Control negativo: esta es exactamente la URL que generaba el
        // defecto. Si vuelve a aparecer, el test cae aunque la de arriba
        // también esté.
        $response->assertDontSee('href="'.$brokenUrl.'"', false);
    }

    /**
     * DEF-01, dirección EN->ES: la cara reportada por Anyerson (404 medido
     * con curl en los 3 tours de 3).
     */
    #[DataProvider('translatedRecords')]
    public function test_the_switcher_on_the_english_page_points_at_the_spanish_slug(string $type): void
    {
        [, $routeName, $esSlug, $enSlug] = $this->makeRecord($type);

        $correctUrl = route($routeName, ['locale' => 'es', 'slug' => $esSlug]);
        $brokenUrl = route($routeName, ['locale' => 'es', 'slug' => $enSlug]);

        $response = $this->get(route($routeName, ['locale' => 'en', 'slug' => $enSlug]));

        $response->assertOk();
        $response->assertSee('href="'.$correctUrl.'"', false);
        $response->assertDontSee('href="'.$brokenUrl.'"', false);
    }

    /**
     * Clic real de extremo a extremo en las DOS direcciones: no basta con que
     * el href sea el esperado, la segunda request tiene que devolver 200
     * directo. Un 301 aquí seguiría siendo un defecto (el visitante llega,
     * pero por una URL que no es la canónica), y un 404 es DEF-01 otra vez.
     */
    #[DataProvider('translatedRecords')]
    public function test_following_the_switcher_link_lands_on_the_translated_page_without_redirecting(string $type): void
    {
        [, $routeName, $esSlug, $enSlug] = $this->makeRecord($type);

        foreach ([
            ['es', $esSlug, 'en'],
            ['en', $enSlug, 'es'],
        ] as [$fromLocale, $fromSlug, $toLocale]) {
            $html = $this->get(route($routeName, ['locale' => $fromLocale, 'slug' => $fromSlug]))
                ->assertOk()
                ->getContent();

            $prefix = url('/'.$toLocale);
            preg_match('~href="('.preg_quote($prefix, '~').'/[^"]+)"~', $html, $matches);

            $this->assertNotEmpty(
                $matches,
                "No se encontro ningun enlace a /{$toLocale}/ en la ficha {$type} servida en /{$fromLocale}/ -- sin extraccion no hay verificacion.",
            );

            $this->get($matches[1])->assertStatus(200);
        }
    }

    /**
     * El caso trampa. "cusco", "arequipa" y "trekking" tienen el MISMO slug
     * en los dos idiomas: cada prefijo sirve su propio contenido y no hay
     * nada que redirigir. Se fija aquí para que un 301 espurio sobre ellos
     * (que rompería tres URLs vivas) caiga en rojo, y para dejar escrito que
     * estos registros NO sirven como cobertura de los dos tests de 301.
     */
    public function test_a_slug_shared_by_both_locales_never_redirects(): void
    {
        Destination::factory()->create([
            'name' => ['es' => 'Cusco', 'en' => 'Cusco'],
            'slug' => ['es' => 'cusco-compartido', 'en' => 'cusco-compartido'],
        ]);

        $this->get(route('destinations.show', ['locale' => 'es', 'slug' => 'cusco-compartido']))->assertOk();
        $this->get(route('destinations.show', ['locale' => 'en', 'slug' => 'cusco-compartido']))->assertOk();
    }

    /**
     * Decisión 3 del encargo: qué pasa cuando NO hay traducción al idioma de
     * destino. Se respeta la decisión ya vigente en el proyecto (aviso +
     * lang del idioma real + noindex, ver App\Models\Concerns\
     * ResolvesBySlugByLocale y x-ui.content-fallback-notice): sin slug propio
     * en inglés no existe URL canónica inglesa a la que redirigir, así que
     * "/en/tours/<slug-es>" sigue sirviendo 200 con el contenido de respaldo.
     * NO es el duplicado de DEF-02: no hay dos URLs con el mismo contenido,
     * hay una sola, y va noindex.
     */
    public function test_a_record_without_english_translation_keeps_serving_the_fallback_instead_of_redirecting(): void
    {
        // La factory crea solo español a proposito (ver TourFactory).
        $tour = Tour::factory()->create();
        $esSlug = $tour->getTranslation('slug', 'es', false);

        $response = $this->get(route('tours.show', ['locale' => 'en', 'slug' => $esSlug]));

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex, nofollow"', false);
        // El bloque de contenido se marca con el idioma REAL del texto: la
        // pagina no finge que esto esta en ingles.
        $response->assertSee('lang="es"', false);
    }

    /**
     * Y el selector, en ese mismo caso, no puede ofrecer un idioma que lleve
     * a un 404 ni desaparecer del header: apunta a la URL de respaldo, que
     * responde 200.
     */
    public function test_the_switcher_still_offers_english_for_an_untranslated_record_and_that_url_answers(): void
    {
        $tour = Tour::factory()->create();
        $esSlug = $tour->getTranslation('slug', 'es', false);

        $fallbackUrl = route('tours.show', ['locale' => 'en', 'slug' => $esSlug]);

        $this->get(route('tours.show', ['locale' => 'es', 'slug' => $esSlug]))
            ->assertOk()
            ->assertSee('href="'.$fallbackUrl.'"', false);

        $this->get($fallbackUrl)->assertOk();
    }

    /**
     * Las pantallas sin slug (home/nosotros/contacto) no dependen de ningún
     * dato traducible: su alterno se sigue reconstruyendo cambiando solo el
     * prefijo. Este test existe porque el arreglo movió ese cálculo de la
     * vista a App\Support\LocaleAlternates -- si el traslado hubiera roto el
     * caso simple, no lo habría notado ningún test de ficha.
     */
    public function test_static_screens_still_switch_locale_by_prefix(): void
    {
        foreach (['home', 'about', 'contact'] as $routeName) {
            $this->get(route($routeName, ['locale' => 'es']))
                ->assertOk()
                ->assertSee('href="'.route($routeName, ['locale' => 'en']).'"', false);
        }
    }
}
