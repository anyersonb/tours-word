<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the agency that a new /contacto message arrived.
 *
 * NOT queued on purpose for now: QUEUE_CONNECTION is "database" in this
 * project and no worker is confirmed running anywhere (local or prod). A
 * queued mailable with nobody running `queue:work` fails exactly the same
 * silent way another project in the studio already got burned by (weeks of
 * mail sitting unsent with nobody noticing) — just one layer further back.
 * ContactMessageController sends this synchronously inside a try/catch so a
 * mail failure never loses the message itself (already persisted first).
 * Revisit once a supervised queue worker exists in production.
 */
class NewContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function build(): self
    {
        return $this
            ->subject('Nuevo mensaje de contacto: '.$this->contactMessage->name)
            ->markdown('emails.contact-message');
    }
}
