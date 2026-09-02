<?php

namespace App\Enums;

/**
 * Difficulty level of a tour. Not translatable by design: it's a fixed
 * taxonomy, its label is resolved through lang files (resources/lang),
 * not stored per-locale in the database.
 */
enum TourDifficulty: string
{
    case Facil = 'facil';
    case Moderado = 'moderado';
    case Dificil = 'dificil';

    public function label(): string
    {
        return match ($this) {
            self::Facil => __('tours.difficulty.facil'),
            self::Moderado => __('tours.difficulty.moderado'),
            self::Dificil => __('tours.difficulty.dificil'),
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
