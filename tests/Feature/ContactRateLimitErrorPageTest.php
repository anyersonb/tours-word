<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** B-4 (docs/rediseno-2026/27-qa-pase25.md): el 429 de /contacto usa la vista de la marca, en lenguaje llano y con WhatsApp. */
class ContactRateLimitErrorPageTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $email): array
    {
        return ['name' => 'Visitante', 'email' => $email, 'message' => 'Hola, quiero informacion de un tour.', 'privacy' => '1'];
    }

    public function test_the_429_on_contact_renders_the_branded_view_in_spanish_with_whatsapp(): void
    {
        Mail::fake();
        for ($i = 0; $i < config('contact.rate_limit_attempts'); $i++) {
            $this->post('/es/contacto', $this->payload("v{$i}@example.com"));
        }

        $this->post('/es/contacto', $this->payload('extra@example.com'))
            ->assertStatus(429)
            ->assertSee('Hiciste varios envíos seguidos', false)
            ->assertSee('Escríbenos por WhatsApp', false)
            ->assertSee(config('brand.contact.whatsapp_url'), false)
            ->assertSee('Volver al inicio', false);
    }

    public function test_the_429_view_exists_in_english_too(): void
    {
        $html = view('errors.429')->render();
        $this->assertStringContainsString('wa.me', $html);
        app()->setLocale('en');
        $this->assertSame('Message us on WhatsApp', __('errors.whatsapp'));
    }
}
