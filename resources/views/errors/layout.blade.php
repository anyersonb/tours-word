@php
    /*
     * Layout AUTÓNOMO de las páginas de error: sin Eloquent, sin Setting, sin
     * header/footer del sitio (que consultan la BD). Un 500 por BD caída no
     * puede volver a fallar al pintarse. Solo usa config(), el idioma de la
     * URL y assets estáticos.
     *
     * Idioma: una ruta inexistente nunca pasa por el middleware `locale`, así
     * que el idioma se lee del primer segmento de la URL; fuera de /es/ y /en/
     * cae al idioma por defecto (es).
     */
    $segment = strtolower((string) request()->segment(1));
    $segments = array_map(
        fn (string $l) => \App\Support\Locale::toSegment($l),
        (array) config('cms.active_locales', ['es']),
    );
    $prefix = in_array($segment, $segments, true) ? $segment : 'es';
    app()->setLocale($prefix === 'en' ? 'en' : 'es');

    $title = __("errors.{$code}.title");
    $text = __("errors.{$code}.text");
    $hasBuild = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="preload" href="{{ asset('fonts/cinzel/Cinzel-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/poppins/poppins-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <style>
        @font-face { font-family: 'Cinzel'; font-style: normal; font-weight: 400 700; font-display: swap; src: url('{{ asset('fonts/cinzel/Cinzel-latin.woff2') }}') format('woff2'); }
        @font-face { font-family: 'Poppins'; font-style: normal; font-weight: 400; font-display: swap; src: url('{{ asset('fonts/poppins/poppins-latin-400-normal.woff2') }}') format('woff2'); }
        @font-face { font-family: 'Poppins'; font-style: normal; font-weight: 600; font-display: swap; src: url('{{ asset('fonts/poppins/poppins-latin-600-normal.woff2') }}') format('woff2'); }
        :root { --espresso: #3e2f24; --ivory: #faf7ee; --gold: #c9a96a; }
        body { margin: 0; background: var(--ivory); color: var(--espresso); font-family: 'Poppins', system-ui, sans-serif; }
    </style>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#3e2f24">
    @if($hasBuild)
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="min-h-screen bg-ground font-sans text-ink antialiased">
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center px-6 py-12 text-center">
        <a href="{{ url('/'.$prefix.'/') }}" aria-label="{{ config('app.name') }}">
            <x-brand.mark variant="full" class="h-auto w-64 sm:w-80" sizes="(min-width: 640px) 20rem, 16rem" />
        </a>

        <p class="mt-6 font-display text-sm font-semibold uppercase tracking-[0.3em] text-gold-deep">{{ __('errors.code_label', ['code' => $code]) }}</p>
        <h1 class="mt-3 font-display text-3xl font-semibold leading-tight text-ink sm:text-4xl">{{ $title }}</h1>
        <p class="mt-4 max-w-xl text-base leading-relaxed text-text-2">{{ $text }}</p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <x-ui.button variant="primary" :href="url('/'.$prefix.'/')">{{ __('errors.home') }}</x-ui.button>
            <x-ui.button variant="secondary" :href="url('/'.$prefix.'/tours')">{{ __('errors.tours') }}</x-ui.button>
            @yield('extra')
        </div>
    </main>
</body>
</html>
