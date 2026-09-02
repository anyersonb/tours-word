<?php

namespace App\Models;

use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'slug',
        'description',
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
    public array $translatable = ['name', 'slug', 'description'];

    /**
     * @return HasMany<Tour, $this>
     */
    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }
}
