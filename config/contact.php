<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact form notification recipient
    |--------------------------------------------------------------------------
    |
    | Where App\Mail\NewContactMessageReceived is sent when a visitor submits
    | /contacto. Deliberately a SEPARATE env var from the public-facing
    | Setting "contact_email" (App\Filament\Pages\Configuracion): the client
    | might want the public email different from the inbox that receives
    | lead notifications, or route it to a shared team inbox.
    |
    | O-2 (docs/lote-3/seguridad-2026-09-14.md, Medio, CONFIRMADO vivo): this
    | used to fall back to MAIL_FROM_ADDRESS ("hello@example.com" in
    | .env/.env.example, a third-party domain nobody at the agency owns) via
    | `?:`, which ALSO falls through when CONTACT_NOTIFY_EMAIL is declared
    | but empty — declaring the var didn't protect anything. With a real
    | SMTP configured, every message from the public form (name, email,
    | phone, free-text message, IP) would have been mailed to that domain.
    |
    | Null here now means "fail closed": no recipient configured, no email
    | ever gets sent to ANY address — never a default that happens to be a
    | third-party domain. App\Http\Controllers\ContactMessageController
    | checks for null explicitly: the message is still saved and a warning
    | is logged (structured, contact_message.notification_skipped_no_recipient)
    | so the missing configuration doesn't fail silently, it just doesn't
    | mail a stranger. Set CONTACT_NOTIFY_EMAIL in .env (local or prod) to
    | receive notifications.
    |
    */

    'notify_email' => filled(env('CONTACT_NOTIFY_EMAIL')) ? env('CONTACT_NOTIFY_EMAIL') : null,

    /*
    |--------------------------------------------------------------------------
    | Rate limit
    |--------------------------------------------------------------------------
    |
    | Applied to POST /contacto via the "throttle" middleware (see
    | routes/web.php). Antispam without a third-party CAPTCHA: a CAPTCHA
    | script is a third party that can collide with the CSP once one exists
    | in production (already happened with analytics on another project in
    | the studio).
    |
    */

    'rate_limit_attempts' => 5,

    'rate_limit_decay_minutes' => 10,

];
