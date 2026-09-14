<?php

namespace App\Http\Controllers;

use App\Enums\ContactMessageStatus;
use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\NewContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        // Honeypot: a field real visitors never see or fill (hidden in
        // contact.blade.php, kept out of the tab order). A bot that fills
        // every field including this one gets a fake "success" — never
        // persisted, never emailed, and never told it was caught.
        if ($request->filled('website')) {
            Log::info('contact_message.honeypot_triggered', ['ip' => $request->ip()]);

            return back()->with('contact_success', true);
        }

        $contactMessage = ContactMessage::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'subject', 'message']),
            'status' => ContactMessageStatus::Nuevo,
            'channel' => 'web',
            'ip_address' => $request->ip(),
            // Ley 29733 (Perú): the message stores WHEN consent was given,
            // not just that validation for the checkbox passed.
            'privacy_consent_at' => now(),
        ]);

        // O-2 (docs/lote-3/seguridad-2026-09-14.md, Medio): config('contact.
        // notify_email') is null when CONTACT_NOTIFY_EMAIL isn't set --
        // fail CLOSED, never fall back to guessing a recipient (that
        // fallback used to be MAIL_FROM_ADDRESS, a third-party domain).
        // The message is still saved either way; only the notification is
        // skipped, and it's logged so the missing configuration is visible
        // instead of silently "working" by mailing a stranger.
        $notifyEmail = config('contact.notify_email');

        if ($notifyEmail === null) {
            Log::warning('contact_message.notification_skipped_no_recipient', [
                'contact_message_id' => $contactMessage->id,
            ]);
        } else {
            // The message is saved first; a mail failure must never lose it.
            try {
                Mail::to($notifyEmail)->send(new NewContactMessageReceived($contactMessage));
            } catch (Throwable $e) {
                Log::error('contact_message.notification_failed', [
                    'contact_message_id' => $contactMessage->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('contact_success', true);
    }
}
