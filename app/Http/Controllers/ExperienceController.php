<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Tour;
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
        $experience = Experience::query()
            ->published()
            ->where("slug->{$locale}", $slug)
            ->with('gallery')
            ->first();

        abort_unless($experience, 404);

        return view('experiences.show', [
            'experience' => $experience,
            'relatedTours' => Tour::query()
                ->published()
                ->ordered()
                ->whereHas('experiences', fn (Builder $query) => $query->whereKey($experience->id))
                ->with(['destination', 'experiences', 'images'])
                ->get(),
        ]);
    }
}
