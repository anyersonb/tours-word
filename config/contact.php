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
    | lead notifications, or route it to a shared team inbox. Falls back to
    | MAIL_FROM_ADDRESS in local/dev so the mailable never fails to resolve a
    | recipient during development.
    |
    */

    'notify_email' => env('CONTACT_NOTIFY_EMAIL') ?: env('MAIL_FROM_ADDRESS', 'hello@example.com'),

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
