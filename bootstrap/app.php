<?php

use App\Http\Middleware\SetLocaleFromUrl;
use App\Support\TrustedHosts;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Laravel 12 registra middleware acá, no en un Kernel.php (lote 1
        // ronda 2, S-08 del informe SEO). Alias usado por routes/web.php
        // para el grupo {locale}.
        $middleware->alias([
            'locale' => SetLocaleFromUrl::class,
        ]);

        // F-1 (docs/lote-3/seguridad-2026-09-14.md, Alto): sin esto, un
        // `Host` hostil se refleja sin validar en robots.txt, sitemap.xml,
        // canonical, hreflang, og:url y la URL de los assets compilados
        // (url()/route()/asset() usan el Host de la request, no
        // config('app.url'), en cuanto hay una request real). Ver
        // App\Support\TrustedHosts para el porque de "extra_trusted_hosts"
        // (el host real todavia no existe: sale de APP_URL + config, no
        // cableado aca) y `subdomains: true` para que un futuro
        // subdominio (ej. "www.") no quede afuera sin tocar código.
        //
        // Nota: Illuminate\Http\Middleware\TrustHosts NO aplica nada cuando
        // app()->environment('local') o runningUnitTests() son true (ver su
        // shouldSpecifyTrustedHosts()) -- a proposito, para que el
        // desarrollo local y la suite de tests no dependan de esta lista.
        // Verificado con curl -H "Host: ..." contra el servidor embebido
        // con APP_ENV=production (docs de entrega); no hay test HTTP que
        // pueda ejercer el 400 real bajo phpunit por la misma razón --
        // TrustedHostsTest.php cubre solo la construcción de la lista.
        $middleware->trustHosts(at: fn () => TrustedHosts::resolve(), subdomains: true);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
