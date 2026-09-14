<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFileOnDelete;
use App\Models\Concerns\HasUniqueSlugPerLocale;
use App\Models\Concerns\ResolvesBySlugByLocale;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use DeletesStoredFileOnDelete, HasFactory, HasTranslations, HasUniqueSlugPerLocale, ResolvesBySlugByLocale;

    protected static function booted(): void
    {
        // Same reasoning as Destination::booted() / Tour::booted():
        // experience_images.experience_id cascades at the DB level, which
        // never fires Eloquent's "deleting" event for the child rows.
        static::deleting(function (Experience $experience): void {
            foreach ($experience->gallery as $image) {
                $image->deleteStoredFile();
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image_path',
        'cover_image_alt',
        'is_published',
        'order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * @var array<int, string>
     */
    public array $translatable = ['name', 'slug', 'description', 'cover_image_alt', 'meta_title', 'meta_description'];

    /**
     * @return BelongsToMany<Tour, $this>
     */
    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class);
    }

    /**
     * @return HasMany<ExperienceImage, $this>
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(ExperienceImage::class)->orderBy('order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Public URL for the cover image, resolved through the "public" disk.
     * Views/Resources must use this accessor — never build the URL by hand.
     * Returns null when no cover image has been uploaded yet.
     */
    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path === null
            ? null
            : Storage::disk('public')->url($this->cover_image_path);
    }

    protected function storedFileAttribute(): string
    {
        return 'cover_image_path';
    }
}
