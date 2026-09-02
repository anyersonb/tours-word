<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

/**
 * Pure formatting/parsing logic — no database involved, so this can live
 * under Unit even though the suite as a whole runs against MySQL.
 */
class MoneyFormattingTest extends TestCase
{
    public function test_format_uses_the_configured_symbols_not_icu_currency_codes(): void
    {
        $this->assertSame('S/ 3,500.00', Money::pen(350000)->format());
        // Regression guard: NumberFormatter::CURRENCY under es_PE renders USD
        // as the bare code "USD" instead of "US$" — format() must not do that.
        $this->assertSame('US$ 95.00', Money::usd(9500)->format());
    }

    public function test_parse_to_cents_accepts_dot_and_comma_decimal_separators(): void
    {
        $this->assertSame(15050, Money::parseToCents('150.50'));
        $this->assertSame(15050, Money::parseToCents('150,50'));
        $this->assertSame(15000, Money::parseToCents(150));
        $this->assertSame(0, Money::parseToCents(''));
    }

    public function test_parse_to_cents_rounds_half_away_from_zero(): void
    {
        // 150.505 -> 15050.5 cents -> rounds to 15051 (not truncated to 15050).
        $this->assertSame(15051, Money::parseToCents('150.505'));
    }

    public function test_decimal_round_trips_with_parse_to_cents(): void
    {
        $money = Money::pen(Money::parseToCents('1234.56'));

        $this->assertSame('1234.56', $money->decimal());
    }

    public function test_add_requires_matching_currencies(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::pen(100)->add(Money::usd(100));
    }

    public function test_add_sums_cents_in_the_same_currency(): void
    {
        $sum = Money::pen(100)->add(Money::pen(250));

        $this->assertSame(350, $sum->cents());
        $this->assertSame('PEN', $sum->currency());
    }
}
