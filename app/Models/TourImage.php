<?php

namespace App\Models;

use Database\Factories\TourImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class TourImage extends Model
{
    /** @use HasFactory<TourImageFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = [
        'tour_id',
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
     * @return BelongsTo<Tour, $this>
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Public URL for the image, resolved through the "public" disk. Views/
     * Resources must use this accessor — never build the URL by hand.
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
