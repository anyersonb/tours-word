<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFileOnDelete;
use App\Models\Concerns\ResolvesBySlugByLocale;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use DeletesStoredFileOnDelete, HasFactory, HasTranslations, ResolvesBySlugByLocale;

    protected static function booted(): void
    {
        // destination_images.destination_id has cascadeOnDelete() at the DB
        // level (see the create_destination_images_table migration): MySQL
        // deletes those rows directly when a Destination is deleted,
        // without loading Eloquent models or firing any event, so
        // DestinationImage's own DeletesStoredFileOnDelete hook never runs
        // for them. Same reasoning and same fix as Tour::booted().
        static::deleting(function (Destination $destination): void {
            foreach ($destination->gallery as $image) {
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
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * @var array<int, string>
     */
    public array $translatable = ['name', 'slug', 'description', 'cover_image_alt'];

    /**
     * @return HasMany<Tour, $this>
     */
    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    /**
     * Ordered gallery, mirroring Tour::images(). Named "gallery" (not
     * "images") to match the key the public views expect -- see
     * resources/views/destinations/{index,show}.blade.php and the
     * DestinationImage docblock.
     *
     * @return HasMany<DestinationImage, $this>
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(DestinationImage::class)->orderBy('order');
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
