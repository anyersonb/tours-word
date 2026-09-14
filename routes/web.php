<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TourController;
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
        // Catálogo y ficha de tours/destinos/experiencias (lote 1,
        // etapa/pantallas). Backend real: App\Http\Controllers\
        // {Tour,Destination,Experience}Controller, contra Tour/Destination/
        // Experience (Eloquent). Reemplaza la maqueta del lote 3
        // (App\Support\CatalogFixtures, ya borrada). Las 6 vistas no se
        // tocaron: leen sus datos por ArrayAccess, que un modelo Eloquent
        // satisface igual que el array de la maqueta (ver el docblock de
        // TourController para el detalle de binding de {locale}).
        // ============================================================

        Route::get('/tours', [TourController::class, 'index'])->name('tours.index');
        Route::get('/tours/{slug}', [TourController::class, 'show'])->name('tours.show');

        Route::get('/destinos', [DestinationController::class, 'index'])->name('destinations.index');
        Route::get('/destinos/{slug}', [DestinationController::class, 'show'])->name('destinations.show');

        Route::get('/experiencias', [ExperienceController::class, 'index'])->name('experiences.index');
        Route::get('/experiencias/{slug}', [ExperienceController::class, 'show'])->name('experiences.show');
    });
