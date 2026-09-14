@props([
    'title' => null,
    'noindex' => false,
    'description' => null,
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
    // Fix 6 (lote SEO, decision ya tomada por Anyerson): locales para los
    // que ESTE registro (Destination/Experience/Tour) tiene traduccion
    // propia -- null (default) significa "no aplica" (home/nosotros/
    // contacto son estaticas, con paridad de claves 1:1 via lang/, nunca
    // dependen de una traduccion de modelo). Cuando se pasa un array, se
    // usa para NO emitir un hreflang hacia un locale cuyo contenido para
    // este registro cae a fallback -- esa URL es noindex (ver "noindex" en
    // destinations/show.blade.php y experiences/show.blade.php), y un
    // hreflang que apunta a una URL noindex es una contradiccion.
    'translatedLocales' => null,
    // Fix 6: reemplazo puntual de la reconstruccion por defecto
    // (route($currentRouteName, [...MISMOS parametros..., 'locale' => X]))
    // para rutas cuyo parametro de slug CAMBIA por locale (Destination/
    // Experience/Tour: "slug" es una columna JSON traducible, no el mismo
    // string en los dos idiomas). Sin esto, el alternate "en" de una ficha
    // reutilizaba el slug en ESPAÑOL de la request actual -- URL que puede
    // no resolver o (peor) resolver por fallback a otro contenido. array
    // opcional locale => URL absoluta; null (default) usa la reconstruccion
    // por defecto, igual que hoy para home/nosotros/contacto.
    'hreflangUrls' => null,
])
@php
    /**
     * SEO (lote 1, arreglo A3 sobre `docs/lote-1/seo-2026-09-02.md`, S-02/S-03/S-12).
     *
     * - `$pageTitle`/`$pageDescription`: cada vista pública debería pasar su
     *   propio `title`/`description`; si no lo hace, cae al fallback de marca
     *   / `site.seo.default_description` (hoy es el caso de
     *   contact.blade.php, fuera de mi reparto de archivos en este lote).
     * - `$canonicalUrl`: NUNCA se arma concatenando `config('app.url')` (ese
     *   es el mismo patrón que ya rompió las imágenes por `APP_URL` apuntando
     *   a un host que no resuelve — Defecto 5 del CRO / S-04 del SEO).
     *   `url()->current()` refleja el host real de la request entrante y,
     *   como no depende de un nombre de ruta ni de un prefijo fijo, sigue
     *   siendo correcto el día que el backend anteponga `/es/`, `/en/`,
     *   `/pt-br/` a las URLs (S-08): no hay nada que reescribir acá.
     * - `hreflang` (objetivo 4, lote i18n 2026-09-14): un alternate por cada
     *   config('cms.active_locales'), generado con la MISMA ruta y los
     *   MISMOS parámetros de la request actual, cambiando solo 'locale' --
     *   nunca una URL inventada. Con un solo locale activo esto ya emitía
     *   un único autorreferencial; con dos o más, cada alternate apunta a
     *   una URL real (200), nunca a una que redirige o da 404: si esto
     *   fuera falso, sería peor que no declarar hreflang. `x-default`
     *   apunta al locale de respaldo (`config('app.fallback_locale')`,
     *   mercado primario hispanohablante, S-08). Solo se emite en páginas
     *   indexables (`@unless($noindex)`) -- las fichas de catálogo hoy son
     *   noindex (contenido de MUESTRA) y no declaran hreflang todavía.
     * - `$ogImageUrl`: no hay todavía una imagen de 1200×630 diseñada para
     *   compartir en redes (las fotos de hoy son placeholders SVG inline, sin
     *   archivo real que enlazar). Como stand-in uso el logo real de marca
     *   (`public/images/brand/logo.svg`) en vez de inventar o de omitir el
     *   tag — pero SVG no es universalmente soportado como og:image (algunos
     *   validadores de Facebook/LinkedIn lo rechazan). Ver aviso en el
     *   reporte: pendiente un JPG/PNG de 1200×630 antes de publicar.
     */
    $pageTitle = $title ? $title.' · '.config('app.name') : config('app.name');
    $pageDescription = $description ?? __('site.seo.default_description');
    $canonicalUrl = $canonical ?? url()->current();
    $ogImageUrl = $ogImage ?? asset('images/brand/logo.svg');

    $currentRouteName = \Illuminate\Support\Facades\Route::currentRouteName();
    $currentRouteParams = \Illuminate\Support\Facades\Route::current()?->parameters() ?? [];

    $hreflangAlternates = collect(config('cms.active_locales'))
        // Fix 6: si el llamador declaro translatedLocales, un locale que NO
        // esta en esa lista significa que la version de ESTE registro en
        // ese locale cae a fallback y es noindex -- su alterno desaparece
        // en vez de apuntar a una URL contradictoria.
        ->when($translatedLocales !== null, fn ($locales) => $locales->intersect($translatedLocales))
        ->mapWithKeys(function (string $loc) use ($currentRouteName, $currentRouteParams, $hreflangUrls) {
            if (isset($hreflangUrls[$loc])) {
                return [$loc => $hreflangUrls[$loc]];
            }

            if (! $currentRouteName) {
                return [];
            }

            $params = array_merge($currentRouteParams, ['locale' => \App\Support\Locale::toSegment($loc)]);

            return [$loc => route($currentRouteName, $params)];
        });

    $xDefaultLocale = config('app.fallback_locale');
    $xDefaultUrl = $hreflangAlternates->get($xDefaultLocale, $canonicalUrl);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    @if($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    @unless($noindex)
        @foreach($hreflangAlternates as $altLocale => $altUrl)
            <link rel="alternate" hreflang="{{ str_replace('_', '-', strtolower($altLocale)) }}" href="{{ $altUrl }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}">

        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @endunless

    {{--
        Único lugar del sitio donde se cargan las tipografías de marca (A2).
        Fraunces (display), Figtree (sans/cuerpo), Caveat (script, uso puntual
        en Nosotros). No hay archivos locales servibles en public/fonts más
        allá de las de Filament, así que se usa Google Fonts como en
        docs/lote-0/identidad/muestra.html.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&family=Figtree:wght@400;500;600;700&family=Caveat:wght@500;600&display=swap" rel="stylesheet">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ground font-sans text-ink antialiased flex flex-col">
    <x-site.header />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
