<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFileOnDelete;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use DeletesStoredFileOnDelete, HasFactory, HasTranslations;

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
