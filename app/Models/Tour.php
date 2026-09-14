<?php

namespace App\Models;

use App\Enums\TourDifficulty;
use App\Models\Concerns\HasUniqueSlugPerLocale;
use App\Models\Concerns\ResolvesBySlugByLocale;
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
    use HasFactory, HasTranslations, HasUniqueSlugPerLocale, ResolvesBySlugByLocale;

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
        'itinerary',
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
        // Same pattern as inclusions/exclusions: translatable JSON array,
        // cast to 'array' so property/array access returns a plain PHP
        // array of {title, description} steps for the current locale (see
        // the add_itinerary_to_tours_table migration).
        'itinerary' => 'array',
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
        'itinerary',
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

        // tour_images.tour_id has cascadeOnDelete() at the DB level (see
        // database/migrations/..._create_tour_images_table.php): MySQL
        // deletes those rows directly when a Tour is deleted, without
        // loading Eloquent models or firing any event. TourImage's own
        // DeletesStoredFileOnDelete hook (its "deleting" event) never runs
        // for a cascaded delete, so the image files would be orphaned in
        // storage if we didn't clean them up here, before the cascade
        // removes the rows.
        static::deleting(function (Tour $tour): void {
            foreach ($tour->images as $image) {
                $image->deleteStoredFile();
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

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
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

    // slugTaken() moved to App\Models\Concerns\HasUniqueSlugPerLocale (O-1,
    // docs/lote-3/seguridad-2026-09-14.md, Bajo): Destination and Experience
    // needed the exact same "whether a given (locale, slug) pair is already
    // used by ANOTHER record" check that only Tour had, and a public catalog
    // that resolves by slug can't afford a second silently-unreachable
    // record. See that trait's docblock for the full reasoning, including
    // why interpolating $locale into `slug->{$locale}` is safe here.
}
