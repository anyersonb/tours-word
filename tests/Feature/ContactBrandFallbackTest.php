<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fuente única de datos de contacto: Setting (CMS) y, si está vacío,
 * config('brand.contact') — la misma cascada que header y footer.
 */
class ContactBrandFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_falls_back_to_brand_config_when_settings_are_empty(): void
    {
        $response = $this->get('/es/contacto')->assertOk();

        $response->assertSee(config('brand.contact.phone'));
        $response->assertSee(config('brand.contact.email'));
        $response->assertSee(config('brand.contact.address'));
        $response->assertDontSee('Todavía no configuramos ningún dato de contacto público');
        $response->assertDontSee('Todavía no configuramos una dirección de oficina');
    }

    public function test_contact_page_prefers_the_setting_over_brand_config(): void
    {
        Setting::set('contact_email', 'cms@ejemplo.test');

        $this->get('/es/contacto')->assertOk()->assertSee('cms@ejemplo.test');
    }

    public function test_contact_page_hides_blocks_when_neither_setting_nor_config_has_data(): void
    {
        config(['brand.contact.phone' => null, 'brand.contact.email' => null, 'brand.contact.address' => null]);

        $this->get('/es/contacto')->assertOk()
            ->assertDontSee('Jr. Carabaya 161');
    }
}
