<?php

namespace App\Support;

use App\Models\Setting;
use InvalidArgumentException;
use NumberFormatter;
use Stringable;

/**
 * Single source of truth for money in the whole app. Every place that shows
 * or converts a price MUST go through this class — no Blade, no Resource, no
 * Livewire component writes "S/" or "$" or multiplies a price by hand.
 *
 * Amounts are always integer cents. The PEN/USD exchange rate is a fixed
 * value the client edits in the CMS (settings.exchange_rate_pen_usd,
 * "how many soles equal one dollar"), never fetched from an external API.
 *
 * This class only formats/converts. Tour::price_pen_cents and
 * Tour::price_usd_cents are stored independently, entered directly by the
 * client — they are NOT derived from convertTo() at save time. convertTo()
 * exists for cases that genuinely need a live conversion (e.g. a future
 * booking flow letting a customer pay the PEN price in USD).
 */
final class Money implements Stringable
{
    public const EXCHANGE_RATE_SETTING_KEY = 'exchange_rate_pen_usd';

    private function __construct(
        private readonly int $cents,
        private readonly string $currency,
    ) {
        if (! in_array($currency, ['PEN', 'USD'], true)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }
    }

    public static function pen(int $cents): self
    {
        return new self($cents, 'PEN');
    }

    public static function usd(int $cents): self
    {
        return new self($cents, 'USD');
    }

    /**
     * Parse a human decimal amount (e.g. "150.50", "150,50", 150.5) typed by
     * an admin in the CMS into integer cents. Centralized here so no form
     * component reimplements float-to-cents parsing.
     */
    public static function parseToCents(string|int|float $amount): int
    {
        $normalized = str_replace(',', '.', (string) $amount);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return 0;
        }

        return (int) round(((float) $normalized) * 100);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * Decimal string with 2 decimals, e.g. "150.50". Use for populating a
     * numeric form input — NOT for display (use format() for that).
     */
    public function decimal(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    /**
     * Locale-correct display string, e.g. "S/ 150.50" / "US$ 150.50".
     *
     * Deliberately does NOT use NumberFormatter::CURRENCY: ICU's es_PE
     * currency symbol table renders USD as the bare code "USD" instead of
     * the "US$" Peruvians actually write. The symbol always comes from
     * config('cms.currencies') — the one place that owns it — while
     * NumberFormatter is only used for locale-correct digit grouping.
     */
    public function format(): string
    {
        $formatter = new NumberFormatter('es_PE', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);

        $amount = $formatter->format($this->cents / 100);
        $symbol = config("cms.currencies.{$this->currency}.symbol", $this->currency);

        return trim($symbol.' '.$amount);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * The fixed PEN-per-USD rate configured by the client in Configuración.
     * Falls back to null if it hasn't been set yet — callers that need a
     * rate to do real work should treat null as "not configured".
     */
    public static function exchangeRate(): ?float
    {
        $rate = Setting::get(self::EXCHANGE_RATE_SETTING_KEY);

        return $rate === null ? null : (float) $rate;
    }

    /**
     * Convert to the other supported currency using the configured rate.
     * Rounding: round-half-away-from-zero on the resulting cents (PHP's
     * default round() mode) — the only rounding rule this app uses for money.
     */
    public function convertTo(string $currency): self
    {
        if ($currency === $this->currency) {
            return $this;
        }

        $rate = self::exchangeRate();

        if ($rate === null || $rate <= 0) {
            throw new \RuntimeException('Exchange rate is not configured (setting "'.self::EXCHANGE_RATE_SETTING_KEY.'").');
        }

        $majorUnits = $this->cents / 100;

        if ($this->currency === 'PEN' && $currency === 'USD') {
            $convertedMajorUnits = $majorUnits / $rate;
        } elseif ($this->currency === 'USD' && $currency === 'PEN') {
            $convertedMajorUnits = $majorUnits * $rate;
        } else {
            throw new InvalidArgumentException("Cannot convert [{$this->currency}] to [{$currency}].");
        }

        return new self((int) round($convertedMajorUnits * 100), $currency);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    public function add(self $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException('Cannot add different currencies.');
        }

        return new self($this->cents + $other->cents, $this->currency);
    }
}
