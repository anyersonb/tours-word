<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use App\Support\RequestedTour;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * DEF-A (docs/lote-3/validacion-visual-2026-09-14.md, punto 4). /contacto
 * used to be a closure in routes/web.php returning view('contact') with no
 * data: the "Reservar este tour" CTA on a tour page landed here and the page
 * had no idea which tour the visitor came from, so the agency received leads
 * that didn't say what they were about.
 *
 * It needs a controller now because the page reads a query parameter
 * (?tour=<slug>) that has to be validated and resolved before it reaches a
 * view — a closure would have to do that inline, and this project already
 * got a 500 out of an unvalidated catalog filter (F-3,
 * docs/lote-3/seguridad-2026-09-14.md). See App\Support\RequestedTour.
 */
class ContactController extends Controller
{
    public function show(Request $request, string $locale): View
    {
        $slug = RequestedTour::slugFrom($request->query());
        $tour = RequestedTour::resolve($slug, $locale);

        // Link back to the ficha with the slug this tour actually publishes
        // in the URL's language: an EN slug requested under /es resolves
        // (fallback lookup) but its canonical URL is the ES one, and sending
        // the visitor to a URL that 301s would be a pointless extra hop.
        $tourUrl = null;

        if ($tour !== null && $slug !== null) {
            $internalLocale = Locale::fromSegment($locale) ?? (string) config('app.fallback_locale');

            $tourUrl = route('tours.show', [
                'locale' => $locale,
                'slug' => $tour->canonicalSlugFor($internalLocale, $slug) ?? $slug,
            ]);
        }

        return view('contact', [
            // null when there is no ?tour=, when it is malformed, and when it
            // points at nothing published. The view renders its plain generic
            // form in all three cases and never prints the requested value.
            'requestedTour' => $tour,
            'requestedTourSlug' => $tour !== null ? $slug : null,
            'requestedTourUrl' => $tourUrl,
        ]);
    }
}
