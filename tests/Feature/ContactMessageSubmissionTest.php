<?php

namespace Tests\Feature;

use App\Mail\NewContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * C1 (lote 1 audit, docs/lote-1/00-sistema-diseno.md): /contacto had a
 * disabled submit button because contact_messages didn't exist. Anyerson
 * pulled the table forward from lote 3 (2026-09-02) — see
 * docs/lote-1/02-fixes-backend-2026-09-02.md.
 */
class ContactMessageSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
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

    public function test_a_visitor_can_submit_the_contact_form_and_it_is_stored_with_a_consent_timestamp(): void
    {
        Mail::fake();

        $response = $this->from('/es/contacto')->post('/es/contacto', $this->validPayload());

        $response->assertRedirect('/es/contacto');
        $response->assertSessionHas('contact_success', true);
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contact_messages', 1);

        $message = ContactMessage::query()->firstOrFail();
        $this->assertSame('Rosa Mamani', $message->name);
        $this->assertSame('rosa@example.com', $message->email);
        $this->assertSame('consulta', $message->subject);
        $this->assertSame('web', $message->channel);
        $this->assertSame('nuevo', $message->status->value);

        // Ley 29733: WHEN consent was given, stored with the message, not
        // just that the checkbox validation passed.
        $this->assertNotNull($message->privacy_consent_at);
        $this->assertTrue($message->privacy_consent_at->greaterThan(now()->subMinute()));

        Mail::assertSent(NewContactMessageReceived::class, fn ($mail) => $mail->contactMessage->is($message));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function requiredFields(): array
    {
        return [
            'name' => ['name'],
            'email' => ['email'],
            'subject' => ['subject'],
            'message' => ['message'],
            'privacy' => ['privacy'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function test_the_form_rejects_a_missing_required_field(string $field): void
    {
        $response = $this->post('/es/contacto', $this->validPayload([$field => '']));

        $response->assertSessionHasErrors([$field]);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_the_form_rejects_an_invalid_email(): void
    {
        $response = $this->post('/es/contacto', $this->validPayload(['email' => 'not-an-email']));

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_the_form_rejects_a_subject_outside_the_allowed_list(): void
    {
        $response = $this->post('/es/contacto', $this->validPayload(['subject' => 'algo-inventado']));

        $response->assertSessionHasErrors(['subject']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    /**
     * Honeypot: a bot that fills EVERY field, including "website" (hidden
     * from real visitors, off-screen and out of the tab order in
     * contact.blade.php), gets a fake success — never persisted, never
     * emailed, and never told it was caught with a validation error.
     */
    public function test_filling_the_honeypot_field_pretends_success_without_persisting_or_emailing(): void
    {
        Mail::fake();

        $response = $this->from('/es/contacto')->post('/es/contacto', $this->validPayload(['website' => 'http://spam.example']));

        $response->assertRedirect('/es/contacto');
        $response->assertSessionHas('contact_success', true);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    /**
     * Antispam without a third-party CAPTCHA: a rate limit on the route
     * (config('contact.rate_limit_attempts') per
     * config('contact.rate_limit_decay_minutes')). Proven both ways: the
     * allowed attempts succeed, and the very next one is throttled.
     */
    public function test_the_route_is_rate_limited_after_the_configured_number_of_attempts(): void
    {
        Mail::fake();

        $attempts = config('contact.rate_limit_attempts');

        for ($i = 0; $i < $attempts; $i++) {
            $this->from('/es/contacto')->post('/es/contacto', $this->validPayload(['email' => "visitor{$i}@example.com"]))
                ->assertRedirect('/es/contacto');
        }

        $this->from('/es/contacto')->post('/es/contacto', $this->validPayload(['email' => 'one-too-many@example.com']))
            ->assertStatus(429);

        // Exactly the allowed number of messages were actually stored — the
        // 429 response never reached the controller.
        $this->assertDatabaseCount('contact_messages', $attempts);
    }

    public function test_a_failed_email_notification_does_not_prevent_the_message_from_being_saved(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP unreachable'));

        $response = $this->from('/es/contacto')->post('/es/contacto', $this->validPayload());

        $response->assertRedirect('/es/contacto');
        $response->assertSessionHas('contact_success', true);
        $this->assertDatabaseCount('contact_messages', 1);
    }
}
