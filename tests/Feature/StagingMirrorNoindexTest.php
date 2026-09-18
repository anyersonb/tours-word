<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Receta SEO `docs/rediseno-2026/02-seo.md` ## 6.2: este build se publica
 * como demo en limaviewtours.com/tour-word/ -- un subdirectorio del dominio
 * de OTRO cliente, que ya sufrió en julio 2026 un incidente real de staging
 * indexado. `cms.is_staging_mirror` es una bandera DEDICADA, independiente
 * de `cms.catalog_demo_content` (que tiene su propio ciclo de vida: se
 * apaga cuando la clienta carga contenido real). Si compartieran la
 * bandera, el día que el catálogo pase a real el staging quedaría
 * indexable sin ningún control dedicado.
 *
 * Cada test fija las DOS banderas explícitamente (nunca confía en el
 * default de config/cms.php) para que el caso bajo prueba quede aislado de
 * cuál sea el valor de producción el día que se lea este archivo -- EXCEPTO
 * el último, que prueba deliberadamente el default sin fijar nada (ver su
 * docblock: hallazgo M-1, default CERRADO a propósito).
 */
class StagingMirrorNoindexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El caso peligroso: con las DOS banderas apagadas (estado de
     * producción real, catálogo ya publicado, fuera del espejo de
     * staging), ninguna página pública debe traer noindex por ningún
     * lado, y el sitemap/robots.txt deben anunciarse con normalidad.
     */
    public function test_no_noindex_anywhere_when_both_flags_are_off(): void
    {
        config([
            'cms.catalog_demo_content' => false,
            'cms.is_staging_mirror' => false,
        ]);

        foreach (['/es', '/es/nosotros', '/es/contacto', '/en', '/en/nosotros', '/en/contacto'] as $path) {
            $page = $this->get($path);
            $page->assertOk();
            $page->assertDontSee('noindex', false);
        }

        $this->get('/sitemap.xml')->assertOk();
        $this->get('/robots.txt')->assertSeeText('Sitemap:');
    }

    /**
     * El defecto que este flag existe para prevenir: aunque el catálogo ya
     * sea contenido real (`catalog_demo_content` en false), si el build es
     * el espejo de staging (`is_staging_mirror` en true) el sitio entero
     * debe salir noindex igual -- home, nosotros y contacto en los dos
     * locales, y el sitemap/robots.txt deben quedar neutralizados como con
     * cualquier otro motivo de noindex de sitio.
     */
    public function test_staging_mirror_flag_alone_forces_site_wide_noindex_even_with_real_content(): void
    {
        config([
            'cms.catalog_demo_content' => false,
            'cms.is_staging_mirror' => true,
        ]);

        foreach (['/es', '/es/nosotros', '/es/contacto', '/en', '/en/nosotros', '/en/contacto'] as $path) {
            $page = $this->get($path);
            $page->assertOk();
            $page->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }

        $this->get('/sitemap.xml')->assertNotFound();

        $robots = $this->get('/robots.txt');
        $robots->assertOk();
        $robots->assertDontSeeText('Sitemap:');
    }

    /**
     * Invariante que NO depende de ninguna de las dos banderas: el permiso
     * de rastreo sigue permitido. Si el crawler no puede descargar la
     * página nunca llega a leer el noindex que la desindexa -- ver
     * feedback_noindex_global_arrastra_sitemap_no_robots en la memoria del
     * equipo.
     */
    public function test_staging_mirror_never_blocks_crawling_only_indexing(): void
    {
        config([
            'cms.catalog_demo_content' => false,
            'cms.is_staging_mirror' => true,
        ]);

        $response = $this->get('/robots.txt');
        $response->assertOk();
        $response->assertSeeText('User-agent: *');
        $response->assertSeeText('Disallow: /admin');

        $lines = array_map('trim', explode("\n", $response->getContent()));
        $this->assertNotContains('Disallow: /', $lines);
        $this->assertNotContains('Disallow: /es', $lines);
        $this->assertNotContains('Disallow: /en', $lines);
    }

    /**
     * Las dos banderas se combinan con OR, nunca se reemplazan entre sí:
     * apagar `is_staging_mirror` con `catalog_demo_content` todavía arriba
     * (estado real de hoy en config/cms.php, DemoTourSeeder) no debe
     * devolver el sitio a indexable.
     */
    public function test_turning_off_staging_mirror_alone_does_not_undo_demo_content_noindex(): void
    {
        $this->assertTrue(config('cms.catalog_demo_content'), 'este test depende del valor por defecto (producción); si cambió, revisar el resto de la suite');

        config(['cms.is_staging_mirror' => false]);

        $response = $this->get('/es');
        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    /**
     * Hallazgo M-1 (docs/rediseno-2026/04-seguridad.md ##5): el default
     * CERRADO (bandera en `true`, sitio en noindex) es DELIBERADO.
     *
     * Este test afirmaba antes justo lo contrario -- que sin la variable
     * de entorno declarada, la bandera debía leer `false` (indexable) --
     * con el argumento de que "nunca debe hacer falta declarar la
     * variable a propósito para que un despliegue NO sea tratado como
     * espejo de staging". Ese default abierto ERA el defecto: medido en
     * vivo por security-engineer, los cinco modos de fallo silenciosos
     * (clave ausente por config:cache viejo, .env perdido en el deploy,
     * typo en el nombre de la variable, valor vacío, "0") dejaban el
     * espejo de staging INDEXABLE en el dominio de producción de OTRO
     * cliente -- que ya sufrió exactamente ese incidente en julio 2026.
     *
     * ADVERTENCIA para quien toque esta prueba en el futuro: "arreglarla"
     * para que vuelva a exigir `false` por defecto DESHACE esta
     * protección sin que nadie se entere -- reabre en silencio el mismo
     * agujero que este fix cerró. Si algún día hace falta que la ausencia
     * de la variable sea indexable, esa es una decisión de negocio que se
     * toma a propósito, con su propio hallazgo documentado, no un test
     * que se relaja para volver a pasar. La contrapartida ya aceptada:
     * ahora el despliegue real de Pacha Viva SÍ tiene que declarar
     * IS_STAGING_MIRROR=false explícito en su .env (ver config/cms.php y
     * .env.example) -- un fallo ruidoso y barato en vez de uno silencioso
     * y caro.
     */
    public function test_is_staging_mirror_defaults_to_true_without_the_env_variable(): void
    {
        $this->assertTrue(config('cms.is_staging_mirror'));
    }
}
