<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function scopeStatus(Builder $query, ContactMessageStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
