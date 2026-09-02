<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * `is_admin` is deliberately excluded and explicitly guarded below: it is
     * the only gate for canAccessPanel(). Not exploitable today (no
     * registration route, no UserResource — see
     * docs/lote-2/seguridad-2026-09-01.md, M-2), but it must stay off any
     * fillable/fill()/create() path so the first `User::create($request->
     * validated())` added in a later batch can't escalate privilege. Assign
     * it only explicitly: `$user->is_admin = true; $user->save();`.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $guarded = [
        'is_admin',
    ];

    /**
     * Gate for the Filament panel: NOT every authenticated user gets in,
     * only accounts explicitly flagged is_admin=true. See the note about
     * the dev-only admin user in docs/lote-2/00-contrato-datos.md — that
     * account must not exist in production.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
