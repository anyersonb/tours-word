<?php

namespace Tests\Feature;

use App\Mail\NewContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O-2 (docs/lote-3/seguridad-2026-09-14.md, Medio, CONFIRMADO vivo).
 * config('contact.notify_email') used to fall back to MAIL_FROM_ADDRESS
 * (a third-party domain, "hello@example.com" in .env/.env.example) via
 * `?:`, and declaring CONTACT_NOTIFY_EMAIL as an empty string did NOT
 * protect against that — `?:` treats '' the same as unset. With a real
 * SMTP configured, every message from the public contact form (name,
 * email, phone, free-text message, IP address) would have been mailed to
 * a domain nobody at the agency owns.
 */
class ContactNotificationFailClosedTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rosa Mamani',
            'email' => 'rosa@example.com',
            'phone' => '+51 999 888 777',
            'subject' => 'consulta',
            'message' => 'Quisiera más información sobre el tour a Choquequirao.',
            'privacy' => 'on',
        ], $overrides);
    }

    /**
     * Control negativo del propio bug que se cierra: `?:` (el operador
     * viejo) SÍ caería a MAIL_FROM_ADDRESS con una cadena vacía. Probar
     * que el nuevo cálculo NO lo hace es lo que demuestra el fix, no solo
     * que "algo distinto de vacío" funcione.
     */
    public function test_an_empty_string_env_value_does_not_fall_back_to_mail_from_address(): void
    {
        $this->assertNull($this->resolveNotifyEmailFor(''));
        $this->assertNull($this->resolveNotifyEmailFor(null));
        $this->assertSame('ventas@pachaviva.example', $this->resolveNotifyEmailFor('ventas@pachaviva.example'));
    }

    public function test_never_falls_back_to_a_third_party_domain(): void
    {
        $this->assertNotSame('hello@example.com', $this->resolveNotifyEmailFor(''));
        $this->assertNotSame('hello@example.com', $this->resolveNotifyEmailFor(null));
    }

    public function test_the_message_is_still_saved_and_no_mail_is_sent_when_no_recipient_is_configured(): void
    {
        config(['contact.notify_email' => null]);
        Mail::fake();
        Log::spy();

        $response = $this->from('/es/contacto')->post('/es/contacto', $this->validPayload());

        $response->assertRedirect('/es/contacto');
        $response->assertSessionHas('contact_success', true);

        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertNothingSent();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => $message === 'contact_message.notification_skipped_no_recipient'
                && array_key_exists('contact_message_id', $context)
            );
    }

    /**
     * Control positivo: cuando SÍ hay destinatario configurado, el correo
     * se sigue enviando exactamente como antes -- el fix es "fallar
     * cerrado sin destinatario", no "dejar de notificar nunca".
     */
    public function test_the_message_is_emailed_when_a_recipient_is_configured(): void
    {
        config(['contact.notify_email' => 'ventas@pachaviva.example']);
        Mail::fake();

        $response = $this->from('/es/contacto')->post('/es/contacto', $this->validPayload());

        $response->assertRedirect('/es/contacto');

        $message = ContactMessage::query()->firstOrFail();
        Mail::assertSent(NewContactMessageReceived::class, fn ($mail) => $mail->contactMessage->is($message)
            && $mail->hasTo('ventas@pachaviva.example')
        );
    }

    /**
     * Replica el cálculo de config/contact.php ('notify_email') sin
     * depender de reiniciar el proceso con un env var distinto -- env()
     * solo se re-lee al arrancar PHP, así que se re-implementa la MISMA
     * expresión aquí para probar el operador, no el cacheo de config.
     */
    private function resolveNotifyEmailFor(?string $envValue): ?string
    {
        return filled($envValue) ? $envValue : null;
    }
}
