<?php

namespace Tests\Unit;

use App\Support\TrustedHosts;
use Tests\TestCase;

/**
 * F-1 (docs/lote-3/seguridad-2026-09-14.md, Alto). Only covers the list
 * TrustedHosts::resolve() builds -- NOT that
 * Illuminate\Http\Middleware\TrustHosts actually rejects a hostile `Host`
 * header, because that middleware is deliberately a no-op under
 * app()->environment('local') and runningUnitTests() (its own
 * shouldSpecifyTrustedHosts()), which is exactly the environment PHPUnit
 * runs in. The real 400 was verified manually with
 * curl -H "Host: evil.example.com" against the embedded server running
 * with APP_ENV=production (see delivery notes) -- a check that CAN'T fail
 * against a broken build here would be worse than no check at all.
 */
class TrustedHostsTest extends TestCase
{
    public function test_it_includes_the_host_derived_from_app_url(): void
    {
        config(['app.url' => 'https://pachaviva.example', 'security.extra_trusted_hosts' => []]);

        $this->assertSame(['pachaviva.example'], TrustedHosts::resolve());
    }

    public function test_it_includes_extra_trusted_hosts_from_config(): void
    {
        config([
            'app.url' => 'https://pachaviva.example',
            'security.extra_trusted_hosts' => ['tours-word.test', '127.0.0.1'],
        ]);

        $this->assertSame(['pachaviva.example', 'tours-word.test', '127.0.0.1'], TrustedHosts::resolve());
    }

    /**
     * Control negativo: un host que nadie declaró (ni APP_URL ni
     * extra_trusted_hosts) nunca debe aparecer en la lista -- si esto
     * fallara, cualquier Host arbitrario sería confiable.
     */
    public function test_it_never_includes_a_host_nobody_declared(): void
    {
        config([
            'app.url' => 'https://pachaviva.example',
            'security.extra_trusted_hosts' => ['tours-word.test'],
        ]);

        $this->assertNotContains('evil.example.com', TrustedHosts::resolve());
    }

    public function test_it_deduplicates_when_app_url_and_extra_hosts_overlap(): void
    {
        config([
            'app.url' => 'https://pachaviva.example',
            'security.extra_trusted_hosts' => ['pachaviva.example', 'tours-word.test'],
        ]);

        $this->assertSame(['pachaviva.example', 'tours-word.test'], TrustedHosts::resolve());
    }

    public function test_it_drops_blank_entries(): void
    {
        config([
            'app.url' => 'https://pachaviva.example',
            'security.extra_trusted_hosts' => ['', 'tours-word.test'],
        ]);

        $this->assertSame(['pachaviva.example', 'tours-word.test'], TrustedHosts::resolve());
    }
}
