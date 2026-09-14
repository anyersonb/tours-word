<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Tour;
use App\Support\Locale;
use App\Support\LocaleAlternates;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Real backend for the destinations catalog/ficha, replacing the
 * "MAQUETA — lote 3" closures in routes/web.php. See App\Http\Controllers\
 * TourController's docblock for the route-parameter-binding note.
 */
class DestinationController extends Controller
{
    public function index(): View
    {
        return view('destinations.index', [
            'destinations' => Destination::query()
                ->published()
                ->ordered()
                ->with('gallery')
                ->get(),
        ]);
    }

    /**
     * $localeAlternates no es un parametro de ruta -- ver el docblock del
     * metodo homonimo en TourController.
     */
    public function show(LocaleAlternates $localeAlternates, string $locale, string $slug): View|RedirectResponse
    {
        // Defecto latente de idioma (docs/lote-3/seguridad-2026-09-14.md,
        // V-3): $locale es el segmento de la URL, no la clave interna de
        // las columnas JSON traducibles -- ver el comentario en
        // TourController::index() para el detalle completo.
        // B-03 (docs/lote-3/seguridad-enrutado-2026-09-14.md): el respaldo no puede
        // ser el segmento CRUDO de la URL. Hoy es inalcanzable (el middleware aborta
        // 404 antes), pero si alguien quitara el middleware de este grupo, ese valor
        // acabaria interpolado como nombre de columna en el camino JSON slug->{...}.
        $internalLocale = Locale::fromSegment($locale) ?? (string) config('app.fallback_locale');

        // Objetivo 2 (lote i18n): mismo fallback de slug que TourController
        // -- ver ResolvesBySlugByLocale.
        $destination = Destination::findBySlugForLocale($internalLocale, $slug, 'gallery');

        abort_unless($destination, 404);

        // DEF-01 / DEF-02 (QA visual 2026-09-14): 301 al slug canonico de
        // este idioma en vez de servir un duplicado, y URLs reales (no
        // adivinadas) para el selector de idioma. Mismo criterio y mismo
        // orden que TourController::show(), donde esta el porque completo.
        if ($canonicalSlug = $destination->canonicalSlugFor($internalLocale, $slug)) {
            return redirect()->route('destinations.show', ['locale' => $locale, 'slug' => $canonicalSlug], 301);
        }

        $localeAlternates->set($destination->urlsByLocale('destinations.show'));

        return view('destinations.show', [
            'destination' => $destination,
            'contentFallbackLocale' => $destination->isContentFallbackFor($internalLocale, 'name')
                ? (string) config('app.fallback_locale')
                : null,
            'relatedTours' => Tour::query()
                ->published()
                ->ordered()
                ->where('destination_id', $destination->id)
                ->with(['destination', 'experiences', 'images'])
                ->get(),
        ]);
    }
}
