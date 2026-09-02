<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_rate_is_read_from_the_settings_table(): void
    {
        Setting::set(Money::EXCHANGE_RATE_SETTING_KEY, 3.8, 'float', 'moneda');

        $this->assertSame(3.8, Money::exchangeRate());
    }

    public function test_exchange_rate_changes_are_visible_without_clearing_any_cache_by_hand(): void
    {
        Setting::set(Money::EXCHANGE_RATE_SETTING_KEY, 3.5, 'float', 'moneda');
        $this->assertSame(3.5, Money::exchangeRate());

        // Simulates the client editing Configuración a second time — the
        // cached value must be busted automatically by Setting::set(), not
        // require an artisan cache:clear.
        Setting::set(Money::EXCHANGE_RATE_SETTING_KEY, 3.9, 'float', 'moneda');
        $this->assertSame(3.9, Money::exchangeRate());
    }

    public function test_convert_pen_to_usd_uses_the_configured_rate_and_rounds_to_the_nearest_cent(): void
    {
        Setting::set(Money::EXCHANGE_RATE_SETTING_KEY, 3.75, 'float', 'moneda');

        // 100.00 PEN / 3.75 = 26.666... USD -> rounds to 26.67 (2667 cents).
        $converted = Money::pen(10000)->convertTo('USD');

        $this->assertSame('USD', $converted->currency());
        $this->assertSame(2667, $converted->cents());
    }

    public function test_convert_usd_to_pen_uses_the_configured_rate(): void
    {
        Setting::set(Money::EXCHANGE_RATE_SETTING_KEY, 3.75, 'float', 'moneda');

        // 50.00 USD * 3.75 = 187.50 PEN (18750 cents) exactly.
        $converted = Money::usd(5000)->convertTo('PEN');

        $this->assertSame('PEN', $converted->currency());
        $this->assertSame(18750, $converted->cents());
    }

    public function test_converting_to_the_same_currency_is_a_no_op(): void
    {
        $money = Money::pen(12345);

        $this->assertTrue($money->equals($money->convertTo('PEN')));
    }

    public function test_conversion_fails_loudly_when_no_rate_is_configured(): void
    {
        $this->expectException(\RuntimeException::class);

        Money::pen(1000)->convertTo('USD');
    }
}
