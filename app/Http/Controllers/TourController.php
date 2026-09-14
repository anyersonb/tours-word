<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Models\TourSlugHistory;
use App\Support\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Real backend for the tours catalog/ficha, replacing the "MAQUETA — lote 3"
 * closures in routes/web.php and App\Support\CatalogFixtures (see that
 * class's docblock for the data contract this must match).
 *
 * NOTE on route parameter binding: unlike the closures it replaces (which
 * matched route segments by POSITION, see the comment that used to sit
 * above the /tours/{slug} route), controller methods match route
 * parameters by NAME (Illuminate\Routing\ControllerDispatcher). Declaring
 * `string $locale` here is enough — Laravel binds it from the {locale}
 * route segment regardless of argument order.
 */
class TourController extends Controller
{
    public function index(Request $request, string $locale): View
    {
        // F-3 (docs/lote-3/seguridad-2026-09-14.md, Medio): $request->query()
        // handed the raw value straight to `where("slug->{$locale}", $value)`
        // -- a slug is always a scalar string, but nothing stopped
        // "?destino[]=" from arriving as an array, which Eloquent's query
        // grammar can't bind and PHP turns into "Array to string
        // conversion" deep inside the query builder: an anonymous, trivially
        // reproducible 500 (`curl '.../tours?destino[]=cusco'`). `valid()`
        // silently drops whichever of the two filters fails validation
        // (wrong type, too long, or characters a real slug never has)
        // instead of letting it reach the query builder OR bouncing the
        // visitor with a 422 for what is, after all, just a catalog filter
        // link that didn't resolve.
        $filters = Validator::make($request->query(), [
            'destino' => ['nullable', 'string', 'alpha_dash', 'max:140'],
            'experiencia' => ['nullable', 'string', 'alpha_dash', 'max:140'],
        ])->valid();

        $destinationSlug = $filters['destino'] ?? null;
        $experienceSlug = $filters['experiencia'] ?? null;

        // Defecto latente de idioma (docs/lote-3/seguridad-2026-09-14.md,
        // V-3): $locale aquí es el SEGMENTO de la URL ("es", "en", y el día
        // que se active, "pt-br"), no la clave interna de las columnas JSON
        // traducibles ("pt_BR" para portugués). Para es/en ambos coinciden
        // por casualidad (el segmento ES la clave), lo que dejó este bug
        // inerte y sin detectar. `Locale::fromSegment()` es la ÚNICA
        // conversión segmento->clave del proyecto (ver su docblock) y
        // siempre resuelve aquí porque SetLocaleFromUrl ya respondió 404
        // para cualquier segmento que no sea un locale activo, antes de
        // que la request llegue a este controller.
        $internalLocale = Locale::fromSegment($locale) ?? $locale;

        $tours = Tour::query()
            ->published()
            ->ordered()
            ->with(['destination', 'experiences', 'images'])
            ->when(
                $destinationSlug,
                fn (Builder $query) => $query->whereHas(
                    'destination',
                    fn (Builder $q) => $q->where("slug->{$internalLocale}", $destinationSlug)
                )
            )
            ->when(
                $experienceSlug,
                fn (Builder $query) => $query->whereHas(
                    'experiences',
                    fn (Builder $q) => $q->where("slug->{$internalLocale}", $experienceSlug)
                )
            )
            ->paginate(6)
            ->withQueryString();

        return view('tours.index', [
            'tours' => $tours,
            'destinationOptions' => Destination::query()->published()->ordered()->get(),
            'experienceOptions' => Experience::query()->published()->ordered()->get(),
            'filters' => [
                'destino' => $destinationSlug,
                'experiencia' => $experienceSlug,
            ],
        ]);
    }

    public function show(string $locale, string $slug): View|RedirectResponse
    {
        // Defecto latente de idioma: $locale es el segmento de la URL, no
        // la clave interna de las columnas JSON traducibles -- ver el
        // comentario equivalente en index(). $locale (el segmento
        // original, sin convertir) sigue viajando tal cual a
        // redirectFromHistory() porque ahí hace falta para construir la
        // URL del 301 (route() espera el segmento, no la clave interna).
        $internalLocale = Locale::fromSegment($locale) ?? $locale;

        // Objetivo 2 (lote i18n): si no hay slug->{$locale}, cae al slug del
        // locale de respaldo (config('app.fallback_locale')) para que
        // "/en/tours/<slug-es>" resuelva en vez de dar 404. Ver el docblock
        // de ResolvesBySlugByLocale para el porque completo.
        $tour = Tour::findBySlugForLocale($internalLocale, $slug, ['destination', 'experiences', 'images']);

        if ($tour) {
            return view('tours.show', [
                'tour' => $tour,
                'contentFallbackLocale' => $tour->isContentFallbackFor($internalLocale, 'title')
                    ? (string) config('app.fallback_locale')
                    : null,
            ]);
        }

        return $this->redirectFromHistory($locale, $slug);
    }

    /**
     * A 404 today at a URL this tour used to live at must become a 301 to
     * where it lives now, never a dead end — tour_slug_histories exists
     * exactly for this (see App\Models\TourSlugHistory's docblock, which
     * predates this route actually using it).
     *
     * $locale here is the URL SEGMENT (needed as-is to build the 301's
     * route() call below); tour_slug_histories.locale stores the INTERNAL
     * locale key (see Tour::booted(), which writes it from
     * getTranslations('slug') keys) — the two only look interchangeable
     * for es/en, where segment and key happen to be identical strings.
     */
    private function redirectFromHistory(string $locale, string $slug): View|RedirectResponse
    {
        $internalLocale = Locale::fromSegment($locale) ?? $locale;

        $history = TourSlugHistory::query()
            ->where('locale', $internalLocale)
            ->where('slug', $slug)
            ->first();

        if ($history) {
            $currentTour = Tour::query()->published()->find($history->tour_id);
            $currentSlug = $currentTour?->getTranslation('slug', $internalLocale, false);

            if (filled($currentSlug)) {
                return redirect()->route('tours.show', ['locale' => $locale, 'slug' => $currentSlug], 301);
            }
        }

        abort(404);
    }
}
