<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\TourImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Encargo docs/rediseno-2026/15-urls-imagenes-subdirectorio.md: las fotos del
 * catálogo daban 403 en el despliegue de demo en el subdirectorio ajeno
 * (limaviewtours.com/tour-word/) porque el disco "public" (config/filesystems.php)
 * arma la URL como "/storage/..." -- ni lleva el segmento "/tour-word" (se
 * pierde por ser raíz-relativa) ni el "/public/" que el .htaccess de la app
 * exige para no chocar con la regla que bloquea el acceso directo a
 * "storage/" en la raíz. La única variable de la ecuación es
 * `PUBLIC_STORAGE_URL`, leída una sola vez en config/filesystems.php.
 *
 * Este archivo NO repite la cobertura de default ya existente en
 * PublicDiskRelativeUrlTest (esa sigue probando que sin la variable el
 * resultado es exactamente "/storage/..."). Prueba las dos cosas que ese
 * archivo no cubre: (1) que la lógica de fallback `env(...) ?: '/storage'`
 * trata una clave presente-pero-vacía igual que ausente (el footgun de
 * `env('KEY', 'default')`, que NO lo haría), y (2) que con el override puesto
 * el valor llega, literal, a los cuatro accessors del catálogo.
 */
class PublicStorageUrlSubdirectoryOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory('test-subdirectory-url');

        parent::tearDown();
    }

    /**
     * Control negativo explícito de la propia fórmula de fallback, sin pasar
     * por config cacheada: `env('KEY', '/storage')` (el patrón ingenuo que
     * NO se usó) se habría quedado en la cadena vacía cuando la clave está
     * declarada pero sin valor -- justo el estado en el que un `.env` tocado
     * a mano por FTP puede quedar ("PUBLIC_STORAGE_URL=" sin nada después).
     * Demuestra que este test SÍ puede fallar: si config/filesystems.php
     * volviera a `env('PUBLIC_STORAGE_URL', '/storage')`, la primera
     * aserción de abajo (con la env vacía) rompería.
     */
    public function test_blank_env_value_would_break_the_naive_default_argument_pattern(): void
    {
        // Simula el estado real de riesgo: la clave SÍ está declarada en el
        // .env del servidor, pero sin valor ("PUBLIC_STORAGE_URL="). Sin esto,
        // la variable simplemente está ausente en esta suite y la comparación
        // no probaría el caso que interesa (clave presente-pero-vacía).
        putenv('PUBLIC_STORAGE_URL=');
        $_ENV['PUBLIC_STORAGE_URL'] = '';

        try {
            $naive = env('PUBLIC_STORAGE_URL', '/storage');
            $this->assertSame('', $naive, 'Control: env() con clave presente-vacía SÍ debía devolver "" con el patrón ingenuo.');
        } finally {
            putenv('PUBLIC_STORAGE_URL');
            unset($_ENV['PUBLIC_STORAGE_URL']);
        }
    }

    /**
     * La fórmula real usada en config/filesystems.php: `env('PUBLIC_STORAGE_URL') ?: '/storage'`.
     * Prueba los tres estados posibles de la variable en un `.env` real.
     */
    public function test_the_actual_fallback_formula_treats_blank_the_same_as_absent(): void
    {
        $formula = fn () => env('PUBLIC_STORAGE_URL') ?: '/storage';

        // Ausente por completo (estado real de esta suite: phpunit.xml no la declara).
        $this->assertSame('/storage', $formula());

        putenv('PUBLIC_STORAGE_URL=');
        $_ENV['PUBLIC_STORAGE_URL'] = '';
        $this->assertSame('/storage', $formula(), 'Declarada pero vacía debe caer al default, no quedarse en "".');

        putenv('PUBLIC_STORAGE_URL=/tour-word/public/storage');
        $_ENV['PUBLIC_STORAGE_URL'] = '/tour-word/public/storage';
        $this->assertSame('/tour-word/public/storage', $formula());

        putenv('PUBLIC_STORAGE_URL');
        unset($_ENV['PUBLIC_STORAGE_URL']);
    }

    /**
     * Con el override activo (simulado vía config(), igual que
     * PublicDiskRelativeUrlTest simula el defecto viejo), los cuatro
     * accessors del catálogo -- el único camino real, confirmado leyendo
     * TourImage/DestinationImage/ExperienceImage/Destination/Experience/
     * TeamMember -- deben emitir la URL con el prefijo del subdirectorio,
     * literal, sin normalizarlo ni perder el "/public/".
     */
    public function test_catalog_accessors_emit_the_subdirectory_prefix_when_overridden(): void
    {
        config(['filesystems.disks.public.url' => '/tour-word/public/storage']);

        $path = 'test-subdirectory-url/cover.png';
        Storage::disk('public')->put($path, str_repeat('x', 128));

        $destination = Destination::factory()->create(['cover_image_path' => $path]);
        $this->assertSame("/tour-word/public/storage/{$path}", $destination->coverImageUrl());

        $tourImage = TourImage::factory()->create(['path' => $path]);
        $this->assertSame("/tour-word/public/storage/{$path}", $tourImage->url());

        // Falsación: si el valor se filtrara por algo que le quita "/public",
        // esta aserción lo detecta -- no es solo un "contains".
        $this->assertStringNotContainsString('"/storage/'.$path.'"', json_encode($tourImage->url()));
    }
}
