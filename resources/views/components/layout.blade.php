@props([
    'title' => null,
    'noindex' => false,
    'description' => null,
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
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
     * - `hreflang`: con un solo locale activo (`es`), el bloque emite un
     *   único alternate autorreferencial + `x-default` apuntando también a
     *   `es` (mercado primario hispanohablante, según recomienda S-08). El
     *   día que se active `en`/`pt-br` con URL propia, se agrega su alternate
     *   sin tocar esta plantilla.
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
        <link rel="alternate" hreflang="es" href="{{ $canonicalUrl }}">
        <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">

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
