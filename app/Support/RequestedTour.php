<?php

namespace App\Support;

use App\Models\Tour;
use Illuminate\Support\Facades\Validator;

/**
 * DEF-A (docs/lote-3/validacion-visual-2026-09-14.md, punto 4). The tour a
 * visitor came from when they landed on /contacto, carried across the two
 * requests of the flow:
 *
 *   GET  /{locale}/contacto?tour=<slug>#formulario   (link on the tour page)
 *   POST /{locale}/contacto  with a hidden "tour" field  (the submission)
 *
 * Both entry points funnel through here on purpose, so the value is
 * normalised and validated the SAME way on the way in and on the way out —
 * it ends up printed on screen and inside an email to the agency.
 *
 * Why Validator::make(...)->valid() and not $request->query('tour'):
 * F-3 of docs/lote-3/seguridad-2026-09-14.md is exactly this shape of bug —
 * "?tour[]=x" arrives as an array and, handed straight to
 * `where("slug->{$locale}", $value)`, blows up inside the query grammar with
 * an anonymous 500 ("Array to string conversion"). valid() drops anything
 * that isn't a scalar slug-shaped string instead of letting it reach the
 * query builder, and instead of bouncing the visitor with a 422 for what is
 * just a deep link that didn't resolve.
 *
 * A slug that is well-formed but matches nothing (or matches an unpublished
 * tour — Tour::findBySlugForLocale() only ever looks at published()) returns
 * null: the contact page renders its generic form and NEVER echoes the
 * requested value back. Nothing that came from the URL is ever printed; only
 * $tour->title, read from the database.
 */
final class RequestedTour
{
    /**
     * Query-string parameter on the GET and hidden field name on the POST.
     */
    public const PARAM = 'tour';

    /**
     * The requested slug, or null if absent/malformed. Never throws.
     *
     * @param  array<array-key, mixed>  $input
     */
    public static function slugFrom(array $input): ?string
    {
        $valid = Validator::make($input, [
            self::PARAM => ['nullable', 'string', 'alpha_dash', 'max:140'],
        ])->valid();

        $slug = $valid[self::PARAM] ?? null;

        return filled($slug) ? (string) $slug : null;
    }

    /**
     * The published tour that slug points at, or null.
     *
     * $localeSegment is the URL segment ("es"/"en"), not the internal
     * translation key — same conversion, and same reason, as
     * App\Http\Controllers\TourController (see its docblock): the fallback is
     * config, never the raw segment, so nothing from the request can ever be
     * interpolated into the `slug->{...}` JSON path.
     */
    public static function resolve(?string $slug, string $localeSegment): ?Tour
    {
        if ($slug === null) {
            return null;
        }

        $internalLocale = Locale::fromSegment($localeSegment) ?? (string) config('app.fallback_locale');

        return Tour::findBySlugForLocale($internalLocale, $slug, ['destination']);
    }
}
