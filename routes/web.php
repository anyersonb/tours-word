<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\SitemapController;
use App\Support\CatalogFixtures;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;

// Prefijo de idioma en TODAS las URLs, incluido español (lote 1 ronda 2,
// decisión de Anyerson sobre S-08 del informe SEO,
// docs/lote-1/seo-2026-09-02.md, Bloque 6): hoy cuesta un Route::group,
// después de la primera indexación cuesta un 301 por URL (precedente: 76
// redirecciones en otro proyecto de la cartera). "/" siempre 301 a "/es/",
// nunca 200 con contenido duplicado.
Route::redirect('/', '/es/', 301);

// S-01 (docs/lote-1/seo-2026-09-02.md, Bloque 1). Sin prefijo de idioma a
// propósito: agrega TODOS los idiomas activos en un único documento, como
// exige el estándar de sitemaps. Fuera del grupo "locale" porque no es
// contenido que dependa del segmento de la URL entrante.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::prefix('{locale}')
    ->where(['locale' => '[A-Za-z-]+'])
    ->middleware('locale')
    ->group(function () {
        // Home pública (lote 1, etapa B). Reemplaza la vista de bienvenida
        // del esqueleto de Laravel, que queda huérfana (ver reporte del
        // lote).
        Route::get('/', function () {
            return view('home');
        })->name('home');

        // Inventario de componentes del sistema de diseño (lote 1, A7). No
        // es parte del sitio público: noindex declarado en la propia vista
        // (x-layout).
        Route::get('/_styleguide', function () {
            return view('styleguide');
        })->name('styleguide');

        // Contacto (lote 1, etapa C). El header/footer ya apuntaban a
        // route('contact') condicionados con Route::has('contact'); con
        // este nombre el enlace del nav pasa a resolver solo, sin tocar
        // esos componentes. El nombre de ruta NO cambió al agregar el
        // prefijo (mandato del lote): route('contact') sigue generando la
        // URL correcta gracias a URL::defaults() en SetLocaleFromUrl.
        Route::get('/contacto', function () {
            return view('contact');
        })->name('contact');

        // Envío real del formulario de contacto (lote 3 adelantado a lote
        // 1, Anyerson 2026-09-02). Límite de tasa como antispam sin CAPTCHA
        // de terceros: config('contact.rate_limit_*') (ver
        // config/contact.php).
        Route::post('/contacto', [ContactMessageController::class, 'store'])
            ->middleware('throttle:'.config('contact.rate_limit_attempts').','.config('contact.rate_limit_decay_minutes'))
            ->name('contact.store');

        // Nosotros (lote 1, etapa D). El header/footer ya apuntaban a
        // route('about') condicionados con Route::has('about'); con este
        // nombre el enlace del nav pasa a resolver solo, sin tocar esos
        // componentes.
        Route::get('/nosotros', function () {
            return view('nosotros');
        })->name('about');

        // ============================================================
        // MAQUETA — lote 3. Sustituir por controladores reales.
        //
        // Catálogo y ficha de tours/destinos/experiencias. Decisión de
        // Anyerson: este lote es maquetación pura, el backend viene
        // después. Los datos de ejemplo salen de App\Support\
        // CatalogFixtures (fuente única, ver esa clase) — estas rutas
        // SOLO leen ese array en memoria, nunca la base de datos, aunque
        // los modelos reales (Tour/Destination/Experience) ya existen
        // desde el lote 2. El contrato de datos exacto que cada vista
        // espera recibir está en el reporte del lote 3.
        // ============================================================

        Route::get('/tours', function (Request $request) {
            $destinationSlug = $request->query('destino');
            $experienceSlug = $request->query('experiencia');

            $tours = array_values(array_filter(CatalogFixtures::tours(), function (array $tour) use ($destinationSlug, $experienceSlug) {
                if ($destinationSlug && ($tour['destination']['slug'] ?? null) !== $destinationSlug) {
                    return false;
                }

                if ($experienceSlug && ! collect($tour['experiences'])->contains('slug', $experienceSlug)) {
                    return false;
                }

                return true;
            }));

            $perPage = 6;
            $page = LengthAwarePaginator::resolveCurrentPage();
            $paginator = new LengthAwarePaginator(
                array_slice($tours, ($page - 1) * $perPage, $perPage),
                count($tours),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => $request->query(),
                ]
            );

            return view('tours.index', [
                'tours' => $paginator,
                'destinationOptions' => CatalogFixtures::destinations(),
                'experienceOptions' => CatalogFixtures::experiences(),
                'filters' => [
                    'destino' => $destinationSlug,
                    'experiencia' => $experienceSlug,
                ],
            ]);
        })->name('tours.index');

        // NOTA: el closure recibe TODOS los parámetros de la ruta por
        // posición (locale, luego slug) — Laravel no los empareja por
        // nombre para closures con parámetros escalares (ver
        // Illuminate\Routing\ResolvesRouteDependencies::resolveMethodDependencies).
        // Omitir $locale aquí hace que $slug reciba 'es' por error: costó
        // una ronda de depuración detectarlo (los 3 index sin {slug}
        // funcionaban bien porque solo tienen un parámetro tipado Request).
        Route::get('/tours/{slug}', function (string $locale, string $slug) {
            $tour = CatalogFixtures::tour($slug);

            abort_unless($tour, 404);

            return view('tours.show', ['tour' => $tour]);
        })->name('tours.show');

        Route::get('/destinos', function () {
            return view('destinations.index', [
                'destinations' => CatalogFixtures::destinations(),
            ]);
        })->name('destinations.index');

        Route::get('/destinos/{slug}', function (string $locale, string $slug) {
            $destination = CatalogFixtures::destination($slug);

            abort_unless($destination, 404);

            return view('destinations.show', [
                'destination' => $destination,
                'relatedTours' => CatalogFixtures::toursByDestination($slug),
            ]);
        })->name('destinations.show');

        Route::get('/experiencias', function () {
            return view('experiences.index', [
                'experiences' => CatalogFixtures::experiences(),
            ]);
        })->name('experiences.index');

        Route::get('/experiencias/{slug}', function (string $locale, string $slug) {
            $experience = CatalogFixtures::experience($slug);

            abort_unless($experience, 404);

            return view('experiences.show', [
                'experience' => $experience,
                'relatedTours' => CatalogFixtures::toursByExperience($slug),
            ]);
        })->name('experiences.show');
    });
