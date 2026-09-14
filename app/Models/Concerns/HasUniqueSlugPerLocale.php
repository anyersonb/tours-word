<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * O-1 (docs/lote-3/seguridad-2026-09-14.md, Bajo). Tour::slugTaken() (S1,
 * lote i18n) was the only one of the three catalog models actually
 * validated for slug uniqueness — Destination and Experience declare
 * `->rule('alpha_dash')` on the slug field but nothing stops two records
 * from sharing the same slug for the same locale. That went from "cosmetic
 * duplicate" to "one of the two becomes unreachable in silence" the moment
 * the lote 3 public catalog started resolving by slug
 * (ResolvesBySlugByLocale::findBySlugForLocale() -> ->first(), which
 * returns whichever row the DB happens to return first): the second
 * destination/experience with the same slug stays published in the CMS but
 * nobody can ever open its page.
 *
 * Extracted into a trait (same pattern as ResolvesBySlugByLocale, which all
 * three models already share) instead of duplicating the same static
 * method three times.
 */
trait HasUniqueSlugPerLocale
{
    /**
     * `slug->{$locale}` interpolates $locale directly into the JSON path.
     * Safe only because every caller (the three *Form Filament classes)
     * already validated $locale against config('cms.locales') before this
     * runs — config('cms.locales') keys, not request input.
     */
    public static function slugTaken(string $locale, string $slug, ?int $exceptId = null): bool
    {
        abort_unless(array_key_exists($locale, config('cms.locales')), 400);

        return static::query()
            ->where("slug->{$locale}", $slug)
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
