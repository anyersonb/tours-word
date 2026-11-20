<?php

namespace Tests\Feature;

use App\Support\Placeholder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Interruptor único de marcadores (config brand.show_placeholders / SHOW_PLACEHOLDERS).
 * Encendido: el Home se pinta tal cual el PDF. Apagado (producción): sin marcadores.
 */
class HomePlaceholdersSwitchTest extends TestCase
{
    use RefreshDatabase;

    private const MARKERS = ['[N] horas', '[N] días', 'S/ [PRECIO]', '[4,9]', '([N] reseñas)', '[N] tours', '[N]+',
        '[Nombre del viajero]', '[4,9] en Tripadvisor', '[medios de pago]', '[Tour sugerido: Ica y Nazca]'];

    public function test_switch_off_hides_every_marker(): void
    {
        config(['brand.show_placeholders' => false]);
        $html = $this->get(route('home', ['locale' => 'es']))->assertOk()->getContent();

        foreach (self::MARKERS as $m) {
            $this->assertStringNotContainsString($m, $html, "marcador visible con el interruptor apagado: $m");
        }
    }

    public function test_switch_on_shows_markers_and_pdf_content_literally(): void
    {
        config(['brand.show_placeholders' => true]);
        $html = $this->get(route('home', ['locale' => 'es']))->assertOk()->getContent();

        foreach (self::MARKERS as $m) {
            $this->assertStringContainsString(e($m), $html, "falta el marcador: $m");
        }
        $this->assertStringContainsString('S/ 3,500', $html);
        $this->assertStringContainsString('Cusco y Machu Picchu', $html);
        $this->assertStringContainsString('Inka Palace Travel. Todos los derechos reservados', $html);
        $this->assertStringContainsString('class="cart"', $html);
        $this->assertStringNotContainsString('hero__pause', $html);
        $this->assertStringNotContainsString('hero__value', $html);
        $this->assertSame(6, substr_count($html, '<div class="acc '), '6 preguntas de FAQ');
    }

    public function test_placeholder_helpers_follow_the_switch(): void
    {
        config(['brand.show_placeholders' => false]);
        $this->assertNull(Placeholder::visible('[N] tours'));
        $this->assertCount(1, Placeholder::items([['a' => '[x]'], ['a' => 'ok']]));

        config(['brand.show_placeholders' => true]);
        $this->assertSame('[N] tours', Placeholder::visible('[N] tours'));
        $this->assertCount(2, Placeholder::items([['a' => '[x]'], ['a' => 'ok']]));
    }
}
