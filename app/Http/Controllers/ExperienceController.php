<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Tour;
use App\Support\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Real backend for the experiences catalog/ficha, replacing the
 * "MAQUETA — lote 3" closures in routes/web.php. See App\Http\Controllers\
 * TourController's docblock for the route-parameter-binding note.
 */
class ExperienceController extends Controller
{
    public function index(): View
    {
        return view('experiences.index', [
            'experiences' => Experience::query()
                ->published()
                ->ordered()
                ->with('gallery')
                ->get(),
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        // Defecto latente de idioma (docs/lote-3/seguridad-2026-09-14.md,
        // V-3): $locale es el segmento de la URL, no la clave interna de
        // las columnas JSON traducibles -- ver el comentario en
        // TourController::index() para el detalle completo.
        $internalLocale = Locale::fromSegment($locale) ?? $locale;

        // Objetivo 2 (lote i18n): mismo fallback de slug que TourController
        // -- ver ResolvesBySlugByLocale.
        $experience = Experience::findBySlugForLocale($internalLocale, $slug, 'gallery');

        abort_unless($experience, 404);

        return view('experiences.show', [
            'experience' => $experience,
            'contentFallbackLocale' => $experience->isContentFallbackFor($internalLocale, 'name')
                ? (string) config('app.fallback_locale')
                : null,
            'relatedTours' => Tour::query()
                ->published()
                ->ordered()
                ->whereHas('experiences', fn (Builder $query) => $query->whereKey($experience->id))
                ->with(['destination', 'experiences', 'images'])
                ->get(),
        ]);
    }
}
