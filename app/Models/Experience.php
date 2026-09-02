<?php

namespace App\Models;

use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
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
     * @return BelongsToMany<Tour, $this>
     */
    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class);
    }
}
