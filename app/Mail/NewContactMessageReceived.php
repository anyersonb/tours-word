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
            ->subject($this->subjectLine())
            ->markdown('emails.contact-message');
    }

    /**
     * DEF-A (docs/lote-3/validacion-visual-2026-09-14.md, punto 4): the tour
     * goes in the SUBJECT LINE, not just in the body. The client's complaint
     * was literally about what she reads in her inbox ("me llegará un correo
     * que dice 'quiero información' sin decir de qué"), and the subject is
     * the only part visible without opening the message.
     *
     * Reads the tour_title SNAPSHOT stored with the message, never
     * $contactMessage->tour->title: the tour can be renamed or deleted after
     * the fact, and the notification must keep saying what the visitor
     * actually asked about.
     */
    private function subjectLine(): string
    {
        $message = $this->contactMessage;

        if (filled($message->tour_title)) {
            return 'Solicitud de reserva: '.$message->tour_title.' — '.$message->name;
        }

        return 'Nuevo mensaje de contacto: '.$message->name;
    }
}
