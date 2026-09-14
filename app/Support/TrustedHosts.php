<?php

namespace App\Support;

/**
 * F-1 (docs/lote-3/seguridad-2026-09-14.md, Alto): the list of hosts
 * `bootstrap/app.php` hands to `Illuminate\Http\Middleware\TrustHosts`.
 * Extracted out of the closure in bootstrap/app.php into its own class for
 * one reason: `TrustHosts` itself refuses to enforce anything while
 * `app()->environment('local')` or `runningUnitTests()` are true (see its
 * `shouldSpecifyTrustedHosts()`), so an HTTP feature test can never exercise
 * the real 400 -- the ONLY way to verify the actual blocking behaviour is
 * `curl` against a server running with APP_ENV=production (done for this
 * fix; see the security report / delivery notes for the before/after). This
 * class is what CAN be unit-tested in isolation: that the resolved list is
 * built correctly from config, never that the middleware enforces it under
 * PHPUnit.
 */
class TrustedHosts
{
    /**
     * @return list<string>
     */
    public static function resolve(): array
    {
        return array_values(array_unique(array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            ...config('security.extra_trusted_hosts'),
        ])));
    }
}
