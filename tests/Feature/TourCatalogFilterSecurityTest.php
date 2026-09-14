<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * F-3 (docs/lote-3/seguridad-2026-09-14.md, Medio, anónimo hoy).
 * TourController::index() handed $request->query('destino')/('experiencia')
 * straight to `where("slug->{$locale}", $value)`. A slug is always a
 * scalar string, but nothing enforced that: "?destino[]=cusco" arrives as
 * an array, which Eloquent's query grammar can't bind — PHP throws "Array
 * to string conversion" deep inside the query builder and the visitor gets
 * an anonymous 500, trivially reproducible with a single query string.
 */
class TourCatalogFilterSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Control positivo: la ruta debe seguir funcionando con filtros
     * legítimos antes de probar que los ilegítimos no la revientan -- si
     * este test fallara, cualquier resultado de los siguientes no probaría
     * nada.
     */
    public function test_a_legitimate_string_filter_still_works(): void
    {
        $destination = Destination::factory()->create(['slug' => ['es' => 'cusco']]);
        Tour::factory()->create(['destination_id' => $destination->id, 'is_published' => true]);

        $response = $this->get('/es/tours?destino=cusco');

        $response->assertOk();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedFilterQueryStrings(): array
    {
        return [
            'array de destino' => ['destino[]=cusco'],
            'array vacio de destino' => ['destino[]='],
            'array de experiencia' => ['experiencia[]=1'],
            'array anidado' => ['destino[a][b]=cusco'],
            'array numerico multiple' => ['destino[]=cusco&destino[]=lima'],
        ];
    }

    #[DataProvider('malformedFilterQueryStrings')]
    public function test_a_malformed_filter_never_produces_a_500(string $queryString): void
    {
        $response = $this->get("/es/tours?{$queryString}");

        $response->assertOk();
        $response->assertDontSee('Array to string conversion');
    }

    /**
     * Tipos y valores absurdos que un visitante (o un bot) puede pegar en
     * la URL a mano: deben ignorarse como filtro, nunca reventar la
     * consulta ni colarse tal cual a la base de datos.
     */
    public static function absurdScalarFilterValues(): array
    {
        return [
            'extremadamente largo' => [str_repeat('a', 5000)],
            'con caracteres de control' => ["cusco\0\n\r"],
            'con comodines SQL' => ["%' OR '1'='1"],
            'unicode con espacios' => ['  cusco andino  '],
        ];
    }

    #[DataProvider('absurdScalarFilterValues')]
    public function test_an_absurd_scalar_filter_value_is_ignored_not_500(string $value): void
    {
        $response = $this->get('/es/tours?'.http_build_query(['destino' => $value]));

        $response->assertOk();
    }

    public function test_the_page_parameter_as_an_array_still_does_not_500(): void
    {
        // Ya se comportaba bien (audit V-3), pero se cubre aquí junto al
        // resto de parametros de la misma ruta para que una regresión
        // futura en el paginador no pase inadvertida.
        $response = $this->get('/es/tours?page[]=2');

        $response->assertOk();
    }
}
