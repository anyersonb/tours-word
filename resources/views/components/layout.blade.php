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
    // Fix 2 (cierre lote SEO, 2026-09-14): cuando la clienta escribe su
    // propio meta_title (Tour/Destination/Experience), ese texto YA trae su
    // propia marca -- componerlo con "· " . config('app.name') encima
    // duplicaba "Pacha Viva" en el <title> ("... | Pacha Viva · Pacha
    // Viva"). "titleLiteral" (default false, sin cambio de comportamiento
    // para home/nosotros/contacto/catalogo, que siempre pasan un titulo de
    // pagina, no de contenido) le dice al layout que NO componga: el
    // llamador ya decidio el <title> completo. Solo se compone cuando
    // $title es el respaldo (nombre del tour/destino/experiencia).
    'titleLiteral' => false,
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
     * - `$ogImageUrl`: JPG real de 1200×630 (`public/images/site/og-default.jpg`),
     *   generado por `scripts/imagenes-derivadas.php` recortando el hero de la
     *   home con el MISMO foco vertical que usa esa pantalla. Reemplaza al logo
     *   SVG que había de stand-in: SVG no es universalmente soportado como
     *   og:image y varios validadores de Facebook/LinkedIn lo rechazan. Es JPG
     *   y no WebP a propósito — hay clientes de mensajería que todavía no
     *   previsualizan WebP, y una miniatura que no se ve es peor que una
     *   pesada.
     *
     * - `$isNoindex` (2026-09-14): mientras `config('cms.catalog_demo_content')`
     *   esté en true, va `noindex, nofollow` en TODO el sitio público, no solo
     *   en el catálogo. Motivo concreto: la portada no emitía robots y su
     *   carrusel pasó a anunciar "Camino Inca a Machu Picchu, 4 días —
     *   S/ 3.500,00", que se lee como una oferta real; un precio inventado
     *   publicado como real es un defecto, no un pendiente. Sale de la MISMA
     *   bandera que ya resuelve las fichas y se resuelve en un solo sitio
     *   (acá), así que bajarla devuelve el sitio entero a indexable sin tocar
     *   código — por eso los tres índices de catálogo dejaron de cablear
     *   `:noindex="true"`.
     *   Receta SEO `## 6.2` (2026-09-18): `$isNoindex` ya NO lee
     *   `config('cms.catalog_demo_content')` directo -- pasa por
     *   `App\Support\Indexability::siteIsIndexable()`, que combina esa
     *   bandera con `cms.is_staging_mirror` (espejo de staging en
     *   limaviewtours.com/tour-word/, dominio de otro cliente). Fuente
     *   única: `SitemapController`/`RobotsController` ya leían esa misma
     *   clase, así que este cambio no les exige nada nuevo.
     */
    $pageTitle = match (true) {
        $titleLiteral && filled($title) => $title,
        filled($title) => $title.' · '.config('app.name'),
        default => config('app.name'),
    };
    $pageDescription = $description ?? __('site.seo.default_description');
    $canonicalUrl = $canonical ?? url()->current();
    $ogImageUrl = $ogImage ?? asset('images/site/og-default.jpg');

    $isNoindex = $noindex || ! \App\Support\Indexability::siteIsIndexable();

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
    @if($isNoindex)
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    {{--
        hreflang sigue atado al noindex POR CONTENIDO ($noindex), no al noindex
        global de obra ($isNoindex). Lo que la nota de arriba prohíbe es un
        grupo hreflang INCOHERENTE -- un alterno noindex apuntado desde uno
        indexable. Mientras la bandera de contenido de muestra está arriba el
        grupo entero es noindex por igual, así que la declaración es coherente
        y queda simplemente inerte hasta que la bandera baje. Si en cambio se
        atara a $isNoindex, al bajar la bandera el sitio volvería sin hreflang
        hasta que alguien se acordara de esto.
    --}}
    @unless($noindex)
        @foreach($hreflangAlternates as $altLocale => $altUrl)
            <link rel="alternate" hreflang="{{ str_replace('_', '-', strtolower($altLocale)) }}" href="{{ $altUrl }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}">
    @endunless

    {{--
        Open Graph / Twitter SIEMPRE, también en páginas noindex. Estaban
        dentro del mismo @unless que el hreflang, mezclando dos asuntos
        distintos: indexar y COMPARTIR. Una URL noindex se sigue pegando en
        WhatsApp o Slack y merece su miniatura; de hecho, mientras el sitio
        está en obra es justo cuando más se comparte a mano.
    --}}
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    @if($ogImage === null)
        {{-- Ancho/alto solo del archivo por defecto, que sí sabemos que mide
             1200x630. Si un día una ficha pasa su propia "ogImage", declarar
             estas medidas a ciegas sería mentir sobre un archivo que no
             conocemos, y un og:image:width falso es peor que ninguno. Sin esto
             varios clientes difieren la tarjeta hasta descargar la imagen y el
             primer compartido sale sin miniatura. --}}
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/jpeg">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    {{--
        Tipografías de marca — port Roavio (2026-09-20). Inter Tight
        (sans/cuerpo) y Fraunces (display) son SIL OFL y ahora se sirven
        AUTOALOJADAS desde public/fonts/ (ver @font-face en app.css) — nunca
        desde fonts.googleapis.com. El preload es solo de esas dos: son las
        que carga TODA página del sitio (body + h1..h6). Caveat (script, uso
        puntual en Nosotros) es la única que queda en Google Fonts: no forma
        parte de este lote y una sola familia de más no justifica bajarla y
        mantenerla también en disco.
    --}}
    <link rel="preload" href="{{ asset('fonts/inter-tight/InterTight-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/fraunces/Fraunces-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    {{--
        Fix subdirectorio (2026-09-21, docs/rediseno-2026/11-fix-fuentes-subdirectorio.md):
        estos @font-face vivían en resources/css/app.css con `url('/fonts/...')`
        absoluto. Vite no reescribe esa URL (no es relativa al archivo fuente ni
        pasa por su pipeline de assets) y queda literal en el CSS compilado: en
        local resuelve porque el sitio sirve en la raíz, pero en staging
        (limaviewtours.com/tour-word/) esa barra pelada apunta a la raíz de OTRO
        sitio -> 404 -> fallback a fuente de sistema. Inline aquí, construido con
        asset(), que sí conoce el prefijo real de despliegue -- mismo mecanismo
        que ya usan los <link rel="preload"> de arriba. Se eligió inline en vez de
        un public/fonts/fonts.css aparte para no sumar un request más a 112 KB de
        fuente que ya compiten con el CSS de Vite. Conserva font-family, los
        rangos de peso, font-style, font-display y los format() tal cual estaban.
    --}}
    <style>
        @font-face {
            font-family: 'Inter Tight';
            font-style: normal;
            font-weight: 100 900;
            font-display: swap;
            src: url('{{ asset('fonts/inter-tight/InterTight-Variable.woff2') }}') format('woff2-variations'),
                 url('{{ asset('fonts/inter-tight/InterTight-Variable.woff2') }}') format('woff2');
        }
        @font-face {
            font-family: 'Fraunces';
            font-style: normal;
            font-weight: 300 700;
            font-display: swap;
            src: url('{{ asset('fonts/fraunces/Fraunces-Variable.woff2') }}') format('woff2-variations'),
                 url('{{ asset('fonts/fraunces/Fraunces-Variable.woff2') }}') format('woff2');
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600&display=swap" rel="stylesheet">

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
