<?php

namespace Tests\Feature;

use App\Filament\Pages\Configuracion;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers the bloqueante fix of the lote-1 audit: Configuracion went from 1
 * field (the exchange rate) to ~20 Setting keys across 4 sections. See
 * app/Filament/Pages/Configuracion.php.
 *
 * The HTTP-level "loads for an admin" test below is deliberate, not
 * redundant with Livewire::test(): in lote 2 this exact page returned a 500
 * from a Blade component that doesn't exist in Filament 4
 * (<x-filament-panels::form.actions>), and it had been declared "working"
 * without ever actually opening the rendered route.
 */
class ConfiguracionSettingsCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_the_settings_page_loads_for_an_admin_without_a_server_error(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/configuracion')
            ->assertOk();
    }

    public function test_a_non_admin_is_denied_access_to_the_settings_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin/configuracion')
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login_instead_of_seeing_the_settings_page(): void
    {
        $this->get('/admin/configuracion')->assertRedirect('/admin/login');
    }

    /**
     * Saves every section and confirms the public front actually reads the
     * values back, not just that Setting rows exist. contact.blade.php
     * prints contact_phone/contact_email/contact_address/contact_schedule
     * verbatim; footer.blade.php (rendered on every page via x-layout)
     * prints company_ruc/company_legal_name.
     */
    public function test_saving_the_form_persists_every_section_and_the_public_site_reads_it_back(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Configuracion::class)
            ->fillForm([
                'exchange_rate_pen_usd' => '3.75',
                'contact_phone' => '+51 999 111 222',
                'contact_email' => 'reservas@pachaviva.pe',
                'contact_address' => 'Av. El Sol 123, Cusco',
                'contact_schedule' => 'Lunes a viernes, 9:00 a 18:00',
                'company_legal_name' => 'Pacha Viva Turismo S.A.C.',
                'company_ruc' => '20601234567',
                'social_instagram_url' => 'https://instagram.com/pachaviva',
                'stat_happy_travelers' => '850',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // The data half of the contract: Setting is the single source of
        // truth every view reads through.
        $this->assertSame('+51 999 111 222', Setting::get('contact_phone'));
        $this->assertSame('reservas@pachaviva.pe', Setting::get('contact_email'));
        $this->assertSame('Pacha Viva Turismo S.A.C.', Setting::get('company_legal_name'));
        $this->assertSame(850, Setting::get('stat_happy_travelers'));

        // The front half: /es/contacto must print what was just saved.
        $contactResponse = $this->get('/es/contacto');
        $contactResponse->assertOk();
        $contactResponse->assertSee('+51 999 111 222');
        $contactResponse->assertSee('reservas@pachaviva.pe');
        $contactResponse->assertSee('Lunes a viernes, 9:00 a 18:00');

        // The footer (company_ruc/company_legal_name) renders on every
        // page via x-layout, so the home page is enough to prove it.
        $homeResponse = $this->get('/es/');
        $homeResponse->assertOk();
        $homeResponse->assertSee('20601234567');
        $homeResponse->assertSee('Pacha Viva Turismo S.A.C.');
    }

    /**
     * The historically weakest point of this whole batch: Setting::get()
     * caches with rememberForever. This must prove the second save is
     * visible WITHOUT calling cache:clear — the two writes hit the SAME
     * cached key, so a regressed cache bust would leave the first (stale)
     * value in place.
     */
    public function test_saving_a_new_value_invalidates_the_cached_setting_without_a_manual_cache_clear(): void
    {
        $this->actingAs($this->admin());

        $page = Livewire::test(Configuracion::class);

        $page->fillForm(['exchange_rate_pen_usd' => '3.75'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsWithDelta(3.75, Setting::get(Money::EXCHANGE_RATE_SETTING_KEY), 0.001);

        // Same request cycle, same cached key, a DIFFERENT value: if the
        // cache bust in Setting::set() regressed, this reads back 3.75
        // (stale) instead of 3.90.
        $page->fillForm(['exchange_rate_pen_usd' => '3.90'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsWithDelta(3.90, Setting::get(Money::EXCHANGE_RATE_SETTING_KEY), 0.001);
    }

    public function test_mount_reads_existing_setting_values_back_into_the_form(): void
    {
        Setting::set('contact_phone', '+51 987 654 321', 'string', 'contacto');

        $this->actingAs($this->admin());

        Livewire::test(Configuracion::class)
            ->assertFormSet(['contact_phone' => '+51 987 654 321']);
    }
}
