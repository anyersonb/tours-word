<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Models\TourSlugHistory;
use App\Support\Locale;
use App\Support\LocaleAlternates;
use App\Support\RequestedTour;
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
        // B-03 (docs/lote-3/seguridad-enrutado-2026-09-14.md): el respaldo no puede
        // ser el segmento CRUDO de la URL. Hoy es inalcanzable (el middleware aborta
        // 404 antes), pero si alguien quitara el middleware de este grupo, ese valor
        // acabaria interpolado como nombre de columna en el camino JSON slug->{...}.
        $internalLocale = Locale::fromSegment($locale) ?? (string) config('app.fallback_locale');

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

    /**
     * $localeAlternates NO es un parametro de ruta: Laravel resuelve por
     * TIPO los argumentos que no coinciden con un segmento de la URL y por
     * NOMBRE los que si (ver el docblock de la clase), asi que puede ir
     * delante de {locale}/{slug} sin afectar su binding.
     */
    public function show(LocaleAlternates $localeAlternates, string $locale, string $slug): View|RedirectResponse
    {
        // Defecto latente de idioma: $locale es el segmento de la URL, no
        // la clave interna de las columnas JSON traducibles -- ver el
        // comentario equivalente en index(). $locale (el segmento
        // original, sin convertir) sigue viajando tal cual a
        // redirectFromHistory() porque ahí hace falta para construir la
        // URL del 301 (route() espera el segmento, no la clave interna).
        // B-03 (docs/lote-3/seguridad-enrutado-2026-09-14.md): el respaldo no puede
        // ser el segmento CRUDO de la URL. Hoy es inalcanzable (el middleware aborta
        // 404 antes), pero si alguien quitara el middleware de este grupo, ese valor
        // acabaria interpolado como nombre de columna en el camino JSON slug->{...}.
        $internalLocale = Locale::fromSegment($locale) ?? (string) config('app.fallback_locale');

        // Objetivo 2 (lote i18n): si no hay slug->{$locale}, cae al slug del
        // locale de respaldo (config('app.fallback_locale')) para que
        // "/en/tours/<slug-es>" resuelva en vez de dar 404. Ver el docblock
        // de ResolvesBySlugByLocale para el porque completo.
        $tour = Tour::findBySlugForLocale($internalLocale, $slug, ['destination', 'experiences', 'images']);

        if ($tour) {
            // DEF-02 (QA visual 2026-09-14): la busqueda de arriba tambien
            // resuelve con el slug de OTRO idioma. Si este tour tiene slug
            // propio en el idioma de la URL, esa otra URL no es una segunda
            // direccion valida: es un duplicado. 301 al canonico, mismo
            // criterio que tour_slug_histories. Sin slug propio no hay
            // canonico y se sigue sirviendo el contenido de respaldo (200 +
            // aviso + noindex) -- ver ResolvesBySlugByLocale.
            if ($canonicalSlug = $tour->canonicalSlugFor($internalLocale, $slug)) {
                return redirect()->route('tours.show', ['locale' => $locale, 'slug' => $canonicalSlug], 301);
            }

            // DEF-01: el selector de idioma del header ya no adivina la URL
            // del otro idioma cambiando el prefijo -- la declara este
            // controller, que es quien tiene el registro y sus slugs.
            $localeAlternates->set($tour->urlsByLocale('tours.show'));

            $fallbackLocale = (string) config('app.fallback_locale');

            // Defecto 2 (auditoria cliente, 2026-09-14): title/summary ya
            // caian correctamente al idioma de respaldo por si solos
            // (Spatie\Translatable\HasTranslations resuelve el fallback
            // para atributos string). itinerary/inclusions/exclusions son
            // atributos array -- Tour::filterTranslations() (fix de causa
            // raiz, ver ese metodo) hace que isContentFallbackFor() tambien
            // los detecte correctamente cuando la traduccion a $internalLocale
            // existe pero esta vacia. No se puede derivar esto de
            // "contentFallbackLocale" (calculado solo sobre "title"): un
            // tour puede tener titulo/resumen en ingles real y, aun asi, el
            // itinerario solo en español.
            return view('tours.show', [
                'tour' => $tour,
                // DEF-A (docs/lote-3/validacion-visual-2026-09-14.md, punto
                // 4): el CTA de reserva llevaba a "/es/contacto" a secas y
                // la ficha se perdía por el camino. Ahora viaja el slug
                // (?tour=) y el ancla al formulario, para no dejar al
                // visitante arriba de la página de contacto.
                //
                // $slug es el canónico de este idioma: si la URL hubiera
                // llegado con el slug de otro, arriba ya respondimos 301.
                // Sale del controller y no de la vista porque es él quien
                // tiene el registro y su slug -- mismo criterio que
                // LocaleAlternates (DEF-01).
                'reserveUrl' => route('contact', [
                    'locale' => $locale,
                    RequestedTour::PARAM => $slug,
                ]).'#formulario',
                'contentFallbackLocale' => $tour->isContentFallbackFor($internalLocale, 'title')
                    ? $fallbackLocale
                    : null,
                'itineraryFallbackLocale' => $tour->isContentFallbackFor($internalLocale, 'itinerary')
                    ? $fallbackLocale
                    : null,
                'inclusionsFallbackLocale' => $tour->isContentFallbackFor($internalLocale, 'inclusions')
                    ? $fallbackLocale
                    : null,
                'exclusionsFallbackLocale' => $tour->isContentFallbackFor($internalLocale, 'exclusions')
                    ? $fallbackLocale
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
        // B-03 (docs/lote-3/seguridad-enrutado-2026-09-14.md): el respaldo no puede
        // ser el segmento CRUDO de la URL. Hoy es inalcanzable (el middleware aborta
        // 404 antes), pero si alguien quitara el middleware de este grupo, ese valor
        // acabaria interpolado como nombre de columna en el camino JSON slug->{...}.
        $internalLocale = Locale::fromSegment($locale) ?? (string) config('app.fallback_locale');

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
