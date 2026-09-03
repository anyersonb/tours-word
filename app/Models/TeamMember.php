<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFileOnDelete;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * "Nuestro equipo" (S2, docs/lote-1/01-esquema-lote1.md): four cards on the
 * Nosotros mockup — photo, name, role, a short bio and social links. This
 * was missing from the lote 2 data contract entirely; it's an omission of
 * that contract, not a new scope item.
 *
 * The table starts empty on purpose: the four people in the mockup are
 * AI-generated placeholders and must never be seeded, not even as
 * "[MUESTRA]" sample data.
 */
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use DeletesStoredFileOnDelete, HasFactory, HasTranslations;

    protected $table = 'team_members';

    protected $fillable = [
        'name',
        'role',
        'description',
        'photo_path',
        'photo_alt',
        'instagram_url',
        'facebook_url',
        'whatsapp_url',
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
    public array $translatable = ['name', 'role', 'description', 'photo_alt'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Public URL for the photo, resolved through the "public" disk.
     * Views/Resources must use this accessor — never build the URL by hand.
     * Returns null when no photo has been uploaded yet.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path === null
            ? null
            : Storage::disk('public')->url($this->photo_path);
    }

    protected function storedFileAttribute(): string
    {
        return 'photo_path';
    }
}
