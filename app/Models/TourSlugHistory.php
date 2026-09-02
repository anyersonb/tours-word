<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (locale, slug) a tour has ever had. Written automatically by
 * App\Models\Tour when a locale's slug changes (see Tour::bootSlugHistory()).
 * No public 301 redirect is built in this lote — this table only guarantees
 * the history isn't lost before that middleware exists.
 */
class TourSlugHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tour_id',
        'locale',
        'slug',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tour, $this>
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
