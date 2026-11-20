<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Home público (rebrand INKA PALACE Travel, lote 1). Lee SOLO contenido
 * publicado del CMS y no rellena huecos con datos inventados: sin datos
 * entrega colecciones vacías y la vista decide el estado vacío.
 *
 * Sin caché a propósito: no hay invalidación automática por modelo/campo
 * todavía (regla del proyecto: sin invalidación, no se cachea). Son pocas
 * filas.
 *
 * Contrato completo (variables, claves, marcadores):
 * docs/lote-1/inka-palace/02-contrato-home.md
 *
 * Localización: el middleware 'locale' (SetLocaleFromUrl) fija el locale de
 * la app y URL::defaults(['locale' => ...]); por eso route('tours.show',
 * ['slug' => ...]) ya sale con el prefijo correcto, y $model->slug /
 * ->name devuelven el valor del idioma actual (con respaldo al idioma por
 * defecto), exactamente igual que en las páginas internas.
 */
class HomeController extends Controller
{
    /** Orden en que se muestran los grupos del filtro por duración. */
    private const PLAN_BUCKETS = ['4h', '1dia', '2-3dias', '4mas'];

    public function __invoke(): View
    {
        $destinations = $this->destinations();
        $planTours = $this->planTours();
        // Franja "Cupos limitados": mismo origen que la tarjeta del grupo 4+ días.
        $incaCard = $planTours->firstWhere('bucket', '4mas');

        return view('home', [
            'hero' => $this->hero($destinations),
            'destinations' => $destinations,
            'experiences' => $this->experiences(),
            'featuredTours' => $this->featuredTours(),
            'planTours' => $planTours,
            'incaTrailPrice' => $this->incaTrailPrice($incaCard),
            'incaTrailPriceUsd' => $this->incaTrailPriceUsd($incaCard),
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $destinations
     * @return array<int, array<string, string|null>>
     */
    private function hero(Collection $destinations): array
    {
        $slides = [];
        $configured = config('brand.hero.slides', []);
        $total = count($configured);

        foreach ($configured as $i => $slide) {
            $text = __('home.hero.slides.'.$slide['slug']);
            $destination = $destinations->firstWhere('key', $slide['slug']);

            $slides[] = [
                'slug' => $slide['slug'],
                'name' => $text['name'],
                'subtitle' => $text['subtitle'],
                'title' => $text['title'],
                'text' => $text['text'],
                'cta_primary' => $text['cta_primary'],
                'cta_secondary' => $text['cta_secondary'],
                // URL pública completa (asset() respeta la subcarpeta de despliegue).
                'image' => asset($slide['image']),
                'thumb' => asset($slide['thumb']),
                'badge' => __('home.hero.badge', ['current' => $i + 1, 'total' => $total, 'name' => $text['name']]),
                // CTA primaria: el destino real si existe publicado; si no, el catálogo.
                'url_primary' => $destination['url'] ?? route('tours.index'),
                'url_secondary' => route('destinations.index'),
            ];
        }

        return $slides;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function destinations(): Collection
    {
        return Destination::query()
            ->published()
            ->withCount(['tours' => fn ($query) => $query->published()])
            ->ordered()
            ->orderBy('id')
            ->get()
            ->map(function (Destination $destination): array {
                $key = $this->stableKey($destination);
                $description = $destination->description;

                return [
                    'name' => (string) $destination->name,
                    // Slug del idioma actual (el que va en la URL).
                    'slug' => (string) $destination->slug,
                    // Slug del idioma por defecto: clave estable para mapas de marca/lang.
                    'key' => $key,
                    'image' => $destination->coverImageUrl() ?? $this->brandImage($key),
                    'tagline' => filled($description)
                        ? (string) $description
                        : $this->langOrNull('home.destinations.taglines.'.$key),
                    // Subtítulo de "Tours por ciudad" (PDF), separado del tagline del mosaico.
                    'city_tagline' => $this->langOrNull('home.cities.taglines.'.$key),
                    'tours_count' => (int) $destination->tours_count,
                    'url' => route('destinations.show', ['slug' => $destination->slug]),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function experiences(): Collection
    {
        return Experience::query()
            ->published()
            ->ordered()
            ->orderBy('id')
            ->get()
            ->map(function (Experience $experience): array {
                $key = $this->stableKey($experience);
                $description = $experience->description;

                return [
                    'name' => (string) $experience->name,
                    'slug' => (string) $experience->slug,
                    'key' => $key,
                    'image' => $experience->coverImageUrl() ?? $this->brandImage($key),
                    'description' => filled($description)
                        ? (string) $description
                        : $this->langOrNull('home.experiences.taglines.'.$key),
                    'link_label' => $this->langOrNull('home.experiences.links.'.$key)
                        ?? __('home.experiences.link_default'),
                    'url' => route('experiences.show', ['slug' => $experience->slug]),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function featuredTours(): Collection
    {
        return Tour::query()
            ->published()
            ->featured()
            ->ordered()
            ->orderBy('id')
            ->with(['destination', 'images'])
            ->get()
            ->map(fn (Tour $tour): array => $this->tourCard($tour))
            // Regla del Home: un tour sin foto no entra a la grilla (la vista la
            // completa con las tarjetas del diseño). Se filtra ANTES del tope.
            ->filter(fn (array $card): bool => filled($card['image']))
            ->take(4)
            ->values();
    }

    /**
     * Un tour por grupo de duración (máx. 4), solo los que se pueden
     * clasificar a partir de duration_label y tienen foto. Sin dato, lista vacía.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function planTours(): Collection
    {
        $byBucket = [];

        Tour::query()
            ->published()
            ->ordered()
            ->orderBy('id')
            ->with(['destination', 'images'])
            ->get()
            ->each(function (Tour $tour) use (&$byBucket): void {
                $bucket = $this->durationBucket((string) $tour->duration_label);

                if ($bucket === null || isset($byBucket[$bucket])) {
                    return;
                }

                $card = $this->tourCard($tour);

                // Un tour sin foto NO reserva el grupo de duración: si lo hiciera,
                // la vista lo descartaría y el grupo quedaría vacío (bug de Planes).
                if (blank($card['image'])) {
                    return;
                }

                $byBucket[$bucket] = $card + [
                    'bucket' => $bucket,
                    'bucket_label' => __('home.plans.filters.'.$bucket),
                    'bucket_kicker' => __('home.plans.bucket_kicker.'.$bucket),
                ];
            });

        return collect(self::PLAN_BUCKETS)
            ->filter(fn (string $bucket): bool => isset($byBucket[$bucket]))
            ->map(fn (string $bucket): array => $byBucket[$bucket])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function tourCard(Tour $tour): array
    {
        $image = $tour->images->first();
        $key = $this->stableKey($tour);

        return [
            'title' => (string) $tour->title,
            'slug' => (string) $tour->slug,
            'key' => $key,
            'destination_name' => $tour->destination ? (string) $tour->destination->name : null,
            'duration_label' => filled($tour->duration_label) ? (string) $tour->duration_label : null,
            // Precio en la moneda por defecto (Money). Para el selector PEN/USD
            // del header, usar price_pen_cents / price_usd_cents con <x-ui.money>.
            'price' => $this->price($tour),
            'price_pen_cents' => $tour->price_pen_cents > 0 ? (int) $tour->price_pen_cents : null,
            'price_usd_cents' => $tour->price_usd_cents > 0 ? (int) $tour->price_usd_cents : null,
            'image' => $image?->url() ?? $this->brandTourImage($key),
            'image_alt' => $image && filled($image->alt) ? (string) $image->alt : (string) $tour->title,
            'url' => route('tours.show', ['slug' => $tour->slug]),
        ];
    }

    /**
     * Precio formateado vía Money en la moneda por defecto del sitio.
     * null si el tour no tiene precio cargado (no mostrar "S/ 0.00").
     */
    private function price(Tour $tour): ?string
    {
        $currency = config('cms.default_currency', 'PEN');
        $cents = $currency === 'USD' ? $tour->price_usd_cents : $tour->price_pen_cents;

        if ($cents === null || $cents <= 0) {
            return null;
        }

        return ($currency === 'USD' ? Money::usd((int) $cents) : Money::pen((int) $cents))->format();
    }

    /**
     * Precio del banner Camino Inca: dato del diseño (soles) formateado con
     * Money. Solo si la moneda por defecto es PEN.
     */
    private function incaTrailPrice(?array $card = null): ?string
    {
        if (config('cms.default_currency', 'PEN') !== 'PEN') {
            return null;
        }

        // Con tarjeta real del grupo 4+ días, la franja usa SU precio PEN.
        if ($card !== null) {
            return $card['price_pen_cents'] !== null ? Money::pen((int) $card['price_pen_cents'])->format() : null;
        }

        return Money::pen((int) config('brand.inca_trail.price_pen_cents'))->format();
    }

    /**
     * Equivalente en USD con el tipo de cambio de Configuración (para el
     * selector de moneda). null si el tipo de cambio no está configurado.
     */
    private function incaTrailPriceUsd(?array $card = null): ?string
    {
        // Mismo origen que la tarjeta: USD del tour si existe; si no, conversión
        // de su PEN con el tipo de cambio de Setting; sin tarjeta, dato de config.
        if ($card !== null && $card['price_usd_cents'] !== null) {
            return Money::usd((int) $card['price_usd_cents'])->format();
        }

        $penCents = $card !== null ? $card['price_pen_cents'] : (int) config('brand.inca_trail.price_pen_cents');

        if ($penCents === null) {
            return null;
        }

        try {
            return Money::pen((int) $penCents)->convertTo('USD')->format();
        } catch (\RuntimeException) {
            return null;
        }
    }

    /**
     * Clasifica duration_label en 4h | 1dia | 2-3dias | 4mas, o null.
     */
    private function durationBucket(string $label): ?string
    {
        if (preg_match('/(\d+)\s*(?:h|horas?)\b/iu', $label, $m) === 1) {
            return (int) $m[1] <= 5 ? '4h' : '1dia';
        }

        if (preg_match('/(\d+)\s*d[ií]as?\b/iu', $label, $m) === 1) {
            $days = (int) $m[1];

            return match (true) {
                $days <= 0 => null,
                $days === 1 => '1dia',
                $days <= 3 => '2-3dias',
                default => '4mas',
            };
        }

        return null;
    }

    /**
     * Slug del idioma por defecto (o el del idioma actual si falta): clave
     * estable para los mapas de marca y lang, independiente del idioma de
     * la URL. Ver docblock de HomeController sobre slugs traducibles.
     */
    private function stableKey(Destination|Experience|Tour $model): string
    {
        $fallback = (string) config('app.fallback_locale');

        return (string) ($model->getTranslation('slug', $fallback, false) ?: $model->slug);
    }

    /** URL pública completa de una imagen provisional por slug, o null. */
    private function brandImage(string $key): ?string
    {
        $path = config('brand.images.'.$key);

        return filled($path) ? asset($path) : null;
    }

    private function brandTourImage(string $key): ?string
    {
        $path = config('brand.fixed_images.tours.'.$key);

        return filled($path) ? asset($path) : null;
    }

    private function langOrNull(string $key): ?string
    {
        $value = __($key);

        return is_string($value) && $value !== $key ? $value : null;
    }
}
