<?php

namespace Tests\Feature;

use App\Filament\Pages\Configuracion;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The lote-2 audit (docs/lote-2/seguridad-2026-09-01.md) reported this page
 * as a 500 for an admin, because the Blade view used
 * <x-filament-panels::form.actions>, a component that doesn't exist in
 * Filament 4.12.8. It also flagged that nobody had ever pressed "Guardar" —
 * these tests exercise the real button, not just the page load.
 */
class ConfiguracionPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_an_admin_can_load_the_configuracion_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/configuracion')
            ->assertOk();
    }

    public function test_an_admin_can_save_a_new_exchange_rate_and_it_is_reflected_without_clearing_any_cache(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Configuracion::class)
            ->fillForm(['exchange_rate_pen_usd' => '3.82'])
            ->call('save')
            ->assertHasNoFormErrors();

        // Read the same way the rest of the app does — Setting::get() /
        // Money::exchangeRate() — with no manual cache invalidation in
        // between. If the field weren't wired through, this would still see
        // the seeded 3.75.
        $this->assertSame(3.82, Setting::get(Money::EXCHANGE_RATE_SETTING_KEY));
        $this->assertSame(3.82, Money::exchangeRate());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidExchangeRates(): array
    {
        return [
            'zero' => [0],
            'negative' => [-3.75],
            'non-numeric' => ['abc'],
        ];
    }

    #[DataProvider('invalidExchangeRates')]
    public function test_the_form_rejects_an_invalid_exchange_rate(mixed $invalidValue): void
    {
        $this->actingAs($this->admin());

        Setting::set(Money::EXCHANGE_RATE_SETTING_KEY, 3.75, 'float', 'moneda');

        Livewire::test(Configuracion::class)
            ->fillForm(['exchange_rate_pen_usd' => $invalidValue])
            ->call('save')
            ->assertHasFormErrors(['exchange_rate_pen_usd']);

        // The bad value must never overwrite what was already configured.
        $this->assertSame(3.75, Setting::get(Money::EXCHANGE_RATE_SETTING_KEY));
    }
}
