<?php

namespace Tests\Feature;

use App\Support\Placeholder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRO mobile (docs/rediseno-2026/19-cro-mobile.md): C-1, C-2, A-2, M-5. */
class CroMobileFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_placeholder_helper(): void
    {
        $this->assertTrue(Placeholder::is('[PRECIO]'));
        $this->assertFalse(Placeholder::is('S/ 100'));
        $this->assertNull(Placeholder::visible('[N] tours'));
        $this->assertSame('Camino Inca', Placeholder::title('[MUESTRA] Camino Inca'));
        $this->assertCount(1, Placeholder::items([['a' => 'ok'], ['a' => '[x]', 'b' => 'ok']]));
    }

    public function test_home_shows_no_bracket_placeholders_and_has_whatsapp_float(): void
    {
        foreach (['/es', '/en'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/>\s*\[[^\]<]{1,80}\]/u', $html);
            $this->assertStringContainsString('data-wa-float', $html);
            $this->assertStringContainsString('?text=', $html);
        }
    }

    public function test_thanks_page_and_llms_txt(): void
    {
        $this->get('/es/gracias')->assertOk()->assertSee('data-wa-float', false);
        $this->get('/en/gracias')->assertOk();
        $this->get('/llms.txt')->assertOk();
    }

    public function test_the_whatsapp_float_is_on_internal_pages(): void
    {
        $this->get('/es/contacto')->assertOk()->assertSee('data-wa-float', false);
        $this->get('/es/tours')->assertOk()->assertSee('data-wa-float', false);
    }
}
