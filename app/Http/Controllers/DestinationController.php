<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Tour;
use Illuminate\Contracts\View\View;

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

    public function show(string $locale, string $slug): View
    {
        $destination = Destination::query()
            ->published()
            ->where("slug->{$locale}", $slug)
            ->with('gallery')
            ->first();

        abort_unless($destination, 404);

        return view('destinations.show', [
            'destination' => $destination,
            'relatedTours' => Tour::query()
                ->published()
                ->ordered()
                ->where('destination_id', $destination->id)
                ->with(['destination', 'experiences', 'images'])
                ->get(),
        ]);
    }
}
