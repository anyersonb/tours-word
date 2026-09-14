<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message sent through the public /contacto form (lote 3 scope, pulled
 * forward into lote 1 — see docs/lote-1/02-fixes-backend-2026-09-02.md).
 *
 * Deliberately NOT populated from raw request input anywhere in the app:
 * App\Http\Controllers\ContactMessageController builds the array it passes
 * to create() itself, mixing validated user input with server-set fields
 * (status, channel, ip_address, privacy_consent_at) — so $fillable staying
 * "loose" here is not a mass-assignment risk the way it would be on User.
 */
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'tour_id',
        'tour_title',
        'message',
        'status',
        'channel',
        'ip_address',
        'privacy_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'privacy_consent_at' => 'datetime',
        ];
    }

    /**
     * DEF-A: the tour the visitor was looking at when they hit "Solicitar
     * reserva". Nullable — the generic /contacto form has no tour — and
     * nullOnDelete at the DB level, so deleting a tour never deletes a lead.
     *
     * Read "tour_title" (a snapshot taken at submit time), not
     * $message->tour->title, anywhere the agency needs to know WHICH tour was
     * asked about: the relation can legitimately be null later on.
     *
     * @return BelongsTo<Tour, $this>
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function scopeStatus(Builder $query, ContactMessageStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
