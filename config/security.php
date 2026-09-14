<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted hosts
    |--------------------------------------------------------------------------
    |
    | F-1 (docs/lote-3/seguridad-2026-09-14.md): without ->trustHosts() in
    | bootstrap/app.php, Laravel's url()/route()/asset() helpers build
    | absolute URLs from whatever `Host` header the REQUEST carried, not
    | from config('app.url'). Laravel only falls back to config('app.url')
    | when there is no request at all (CLI/queue). A request with
    | `Host: evil.example.com` therefore poisons robots.txt, sitemap.xml,
    | canonical, hreflang, og:url and the URL of the built JS/CSS assets —
    | verified with `curl -H "Host: evil.example.com"` against the
    | project's embedded server (audit report, F-1).
    |
    | The host derived from APP_URL is trusted by default: that covers the
    | real deploy target once a domain exists, without cabling one here.
    | EXTRA_TRUSTED_HOSTS covers hosts that legitimately do NOT match
    | APP_URL — chiefly local development, where Laragon serves the site
    | through its own vhost (tours-word.test) while APP_URL still points at
    | http://127.0.0.1:8000 (the `php artisan serve` default). Comma
    | separated, e.g. "tours-word.test,127.0.0.1,localhost".
    |
    | Any Host header that matches neither list gets Symfony's 400
    | "Invalid Host header" instead of a page — see
    | Illuminate\Http\Middleware\TrustHosts.
    |
    */

    'extra_trusted_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EXTRA_TRUSTED_HOSTS', ''))
    ))),

];
