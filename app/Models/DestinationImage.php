<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFileOnDelete;
use Database\Factories\DestinationImageFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * Mirrors App\Models\TourImage exactly (see that class and the
 * create_destination_images_table migration for the full rationale).
 */
class DestinationImage extends Model
{
    /** @use HasFactory<DestinationImageFactory> */
    use DeletesStoredFileOnDelete, HasFactory, HasTranslations;

    protected $fillable = [
        'destination_id',
        'path',
        'alt',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * @var array<int, string>
     */
    public array $translatable = ['alt'];

    /**
     * @return BelongsTo<Destination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    protected function src(): Attribute
    {
        return Attribute::make(get: fn () => $this->url());
    }

    protected function storedFileAttribute(): string
    {
        return 'path';
    }
}
