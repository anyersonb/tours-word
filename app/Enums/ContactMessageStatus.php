<?php

namespace App\Enums;

/**
 * Attention status of an inbound /contacto message. Internal to the admin
 * panel only — never shown on the public site, so labels are plain Spanish
 * literals like the rest of app/Filament (see App\Enums\TourDifficulty for
 * the front-facing equivalent, which DOES go through lang files).
 */
enum ContactMessageStatus: string
{
    case Nuevo = 'nuevo';
    case EnProceso = 'en_proceso';
    case Atendido = 'atendido';

    public function label(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo',
            self::EnProceso => 'En proceso',
            self::Atendido => 'Atendido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Nuevo => 'danger',
            self::EnProceso => 'warning',
            self::Atendido => 'success',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
