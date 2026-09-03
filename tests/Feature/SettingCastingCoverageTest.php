<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers a real bug from ronda 1 (docs/lote-1): Setting::set() used to
 * persist an empty string when a field was cleared, and (int) '' === 0 —
 * so a blank "viajeros felices" stat would have published "0 viajeros
 * felices" on the Home. Setting::set() now always stores a real NULL for a
 * null $value, and castValue() returns null instead of casting it. This
 * file exercises Setting::get()/set() directly (not through the
 * Configuracion panel page — that wiring is covered by
 * ConfiguracionSettingsFormTest) for every type the "settings" migration
 * declares: string, integer, float, boolean, json.
 */
class SettingCastingCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_string_value_round_trips(): void
    {
        Setting::set('contact_email_test', 'hola@pachaviva.pe', 'string', 'contacto');

        $this->assertSame('hola@pachaviva.pe', Setting::get('contact_email_test'));
    }

    public function test_an_integer_value_round_trips(): void
    {
        Setting::set('stat_tours_completed_test', 120, 'integer', 'cifras');

        $this->assertSame(120, Setting::get('stat_tours_completed_test'));
    }

    public function test_a_float_value_round_trips(): void
    {
        Setting::set('exchange_rate_pen_usd_test', 3.75, 'float', 'moneda');

        $this->assertSame(3.75, Setting::get('exchange_rate_pen_usd_test'));
    }

    /**
     * Both booleans matter: false must NOT be confused with "no row" (which
     * also reads back as a falsy-looking null via the $default param).
     */
    public function test_boolean_values_round_trip_including_false(): void
    {
        Setting::set('newsletter_enabled_test', true, 'boolean', 'flags');
        $this->assertSame(true, Setting::get('newsletter_enabled_test'));

        Setting::set('newsletter_enabled_test', false, 'boolean', 'flags');
        $this->assertSame(false, Setting::get('newsletter_enabled_test'));
    }

    public function test_a_json_value_round_trips(): void
    {
        Setting::set('nav_links_test', ['a' => 1, 'b' => [2, 3]], 'json', 'nav');

        $this->assertSame(['a' => 1, 'b' => [2, 3]], Setting::get('nav_links_test'));
    }

    public function test_get_returns_the_given_default_when_the_key_does_not_exist(): void
    {
        $this->assertNull(Setting::get('key_that_was_never_set_test'));
        $this->assertSame('fallback', Setting::get('key_that_was_never_set_test', 'fallback'));
    }

    /**
     * The regression itself. A real value is seeded FIRST — if the test
     * started from an empty settings table, it couldn't tell "cleared to
     * null on purpose" apart from "set() silently did nothing", because
     * both would read back as null. Seeding 500 first means a broken
     * set()/castValue() that goes back to storing/reading '' as 0 (or that
     * simply no-ops) turns this test red either way.
     */
    public function test_clearing_a_stat_persists_null_never_a_publishable_zero(): void
    {
        Setting::set('stat_happy_travelers', 500, 'integer', 'cifras');
        $this->assertSame(500, Setting::get('stat_happy_travelers'));

        // The client clears the field in the CMS: Configuracion::save()
        // passes null for a blank TextInput (see filled($value) there).
        Setting::set('stat_happy_travelers', null, 'integer', 'cifras');

        $this->assertNull(
            Setting::get('stat_happy_travelers'),
            'A blank stat must read back as null, never as a publishable 0 travelers.'
        );
    }
}
