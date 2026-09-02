<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Generic typed key/value store for site configuration.
 *
 * This is the ONLY place the exchange rate lives (key "exchange_rate_pen_usd").
 * It's a fixed value the client edits in the CMS — never fetched from an
 * external API. App\Support\Money reads it through Setting::get(), which is
 * cached forever and busted on write, so a config change is visible
 * immediately without a manual cache:clear.
 */
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const CACHE_PREFIX = 'setting:';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Read a setting's value, cast according to its "type" column.
     * Cached forever; the cache is busted by set()/save() below.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return $setting->castValue();
        });
    }

    /**
     * Create or update a setting and bust its cache entry.
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (is_array($value) ? json_encode($value) : (string) $value),
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget(self::CACHE_PREFIX.$key);

        return $setting;
    }

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => Cache::forget(self::CACHE_PREFIX.$setting->key));
        static::deleted(fn (self $setting) => Cache::forget(self::CACHE_PREFIX.$setting->key));
    }

    private function castValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }
}
