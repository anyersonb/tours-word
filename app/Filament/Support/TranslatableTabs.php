<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Builds one Filament Tab per ACTIVE locale (config('cms.active_locales')),
 * not per locale the schema supports (config('cms.locales')). Today only
 * "es" is active, so every translatable resource shows a single tab; when
 * lote 5 activates "en"/"pt_BR" the extra tabs appear automatically here
 * without touching any Resource form.
 *
 * Usage: TranslatableTabs::make(fn (string $locale) => [
 *     TextInput::make("title.$locale")->required($locale === 'es'),
 * ])
 */
class TranslatableTabs
{
    public static function make(\Closure $fieldsForLocale, ?string $label = null): Tabs
    {
        $locales = config('cms.locales');
        $active = config('cms.active_locales');

        return Tabs::make($label ?? 'Idiomas')
            ->tabs(
                collect($active)
                    ->map(fn (string $locale) => Tab::make($locales[$locale] ?? $locale)
                        ->schema($fieldsForLocale($locale)))
                    ->all()
            );
    }
}
