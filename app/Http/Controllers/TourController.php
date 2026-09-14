<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Models\TourSlugHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $destinationSlug = $request->query('destino');
        $experienceSlug = $request->query('experiencia');

        $tours = Tour::query()
            ->published()
            ->ordered()
            ->with(['destination', 'experiences', 'images'])
            ->when(
                $destinationSlug,
                fn (Builder $query) => $query->whereHas(
                    'destination',
                    fn (Builder $q) => $q->where("slug->{$locale}", $destinationSlug)
                )
            )
            ->when(
                $experienceSlug,
                fn (Builder $query) => $query->whereHas(
                    'experiences',
                    fn (Builder $q) => $q->where("slug->{$locale}", $experienceSlug)
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
        $tour = Tour::query()
            ->published()
            ->where("slug->{$locale}", $slug)
            ->with(['destination', 'experiences', 'images'])
            ->first();

        if ($tour) {
            return view('tours.show', ['tour' => $tour]);
        }

        return $this->redirectFromHistory($locale, $slug);
    }

    /**
     * A 404 today at a URL this tour used to live at must become a 301 to
     * where it lives now, never a dead end — tour_slug_histories exists
     * exactly for this (see App\Models\TourSlugHistory's docblock, which
     * predates this route actually using it).
     */
    private function redirectFromHistory(string $locale, string $slug): View|RedirectResponse
    {
        $history = TourSlugHistory::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        if ($history) {
            $currentTour = Tour::query()->published()->find($history->tour_id);
            $currentSlug = $currentTour?->getTranslation('slug', $locale, false);

            if (filled($currentSlug)) {
                return redirect()->route('tours.show', ['locale' => $locale, 'slug' => $currentSlug], 301);
            }
        }

        abort(404);
    }
}
