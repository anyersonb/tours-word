<?php

namespace App\Models;

use App\Enums\TourDifficulty;
use App\Support\Money;
use Database\Factories\TourFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Tour extends Model
{
    /** @use HasFactory<TourFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = [
        'destination_id',
        'title',
        'slug',
        'summary',
        'description',
        'duration_label',
        'difficulty',
        'meeting_point',
        'inclusions',
        'exclusions',
        'price_pen_cents',
        'price_usd_cents',
        'is_featured',
        'is_published',
        'order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'price_pen_cents' => 'integer',
        'price_usd_cents' => 'integer',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'order' => 'integer',
        'difficulty' => TourDifficulty::class,
        'inclusions' => 'array',
        'exclusions' => 'array',
    ];

    /**
     * @var array<int, string>
     */
    public array $translatable = [
        'title',
        'slug',
        'summary',
        'description',
        'duration_label',
        'meeting_point',
        'inclusions',
        'exclusions',
        'meta_title',
        'meta_description',
    ];

    protected static function booted(): void
    {
        static::updating(function (Tour $tour): void {
            $original = $tour->getOriginal('slug');
            $originalSlugs = is_string($original) ? (json_decode($original, true) ?: []) : (array) $original;
            $currentSlugs = $tour->getTranslations('slug');

            foreach ($originalSlugs as $locale => $oldSlug) {
                $newSlug = $currentSlugs[$locale] ?? null;

                if ($oldSlug !== null && $oldSlug !== '' && $oldSlug !== $newSlug) {
                    TourSlugHistory::query()->firstOrCreate([
                        'tour_id' => $tour->id,
                        'locale' => $locale,
                        'slug' => $oldSlug,
                    ], [
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }

    /**
     * @return BelongsTo<Destination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    /**
     * @return BelongsToMany<Experience, $this>
     */
    public function experiences(): BelongsToMany
    {
        return $this->belongsToMany(Experience::class);
    }

    /**
     * @return HasMany<TourImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(TourImage::class)->orderBy('order');
    }

    /**
     * @return HasMany<TourSlugHistory, $this>
     */
    public function slugHistories(): HasMany
    {
        return $this->hasMany(TourSlugHistory::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    public function priceInPen(): Money
    {
        return Money::pen($this->price_pen_cents);
    }

    public function priceInUsd(): Money
    {
        return Money::usd($this->price_usd_cents);
    }

    /**
     * Whether a given (locale, slug) pair is already used by ANOTHER tour.
     * Used by the Filament form's uniqueness validation, since a JSON
     * translatable column can't carry a native per-locale unique index.
     *
     * $locale is interpolated into the JSON path (`slug->{$locale}`). Today
     * it always comes from config('cms.active_locales') via TourForm's
     * TranslatableTabs closure — never from the request — and the security
     * audit (docs/lote-2/seguridad-2026-09-01.md, B-0) confirmed this isn't
     * SQL injection (the quote gets doubled correctly). Still, the day a
     * caller resolves a public slug URL and passes a request-controlled
     * locale here, it must be checked against the whitelist first.
     */
    public static function slugTaken(string $locale, string $slug, ?int $exceptId = null): bool
    {
        abort_unless(array_key_exists($locale, config('cms.locales')), 400);

        return static::query()
            ->where("slug->{$locale}", $slug)
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
