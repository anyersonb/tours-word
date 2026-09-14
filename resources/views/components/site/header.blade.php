@php
    /*
     * Pase visual 2026-09-14: 'active' estaba cableado a false en los cinco
     * ítems que no son Inicio, así que el sitio nunca marcaba dónde estabas.
     * Ahora sale de request()->routeIs() con comodín, para que la ficha
     * (tours.show) también marque su índice.
     */
    $navItems = [
        ['label' => __('site.nav.home'), 'route' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => __('site.nav.tours'), 'route' => Route::has('tours.index') ? route('tours.index') : '#', 'active' => request()->routeIs('tours.*')],
        ['label' => __('site.nav.destinations'), 'route' => Route::has('destinations.index') ? route('destinations.index') : '#', 'active' => request()->routeIs('destinations.*')],
        ['label' => __('site.nav.experiences'), 'route' => Route::has('experiences.index') ? route('experiences.index') : '#', 'active' => request()->routeIs('experiences.*')],
        ['label' => __('site.nav.about'), 'route' => Route::has('about') ? route('about') : '#', 'active' => request()->routeIs('about')],
        ['label' => __('site.nav.contact'), 'route' => Route::has('contact') ? route('contact') : '#', 'active' => request()->routeIs('contact*')],
    ];

    // Objetivo (lote i18n, 2026-09-14): el selector de idioma solo ofrece
    // locales ACTIVOS. "locales" (config('cms.locales')) es el esquema
    // completo (incluye pt_BR, todavía fuera del proyecto); filtrar acá
    // evita que el componente tenga que fingir un estado "próximamente"
    // para un idioma que no va a existir (Anyerson, 2026-09-10).
    $activeLocales = config('cms.active_locales', []);
    $locales = collect(config('cms.locales', []))->only($activeLocales)->all();
    $currentLocale = app()->getLocale();

    $currencies = config('cms.currencies', []);

    // URL de cada locale activo para la MISMA pantalla que se está viendo.
    //
    // DEF-01 (QA visual 2026-09-14): esto se calculaba acá reconstruyendo la
    // ruta actual con los MISMOS parámetros y cambiando solo 'locale'. En
    // una ficha el {slug} es una columna JSON traducible, así que desde
    // "/en/tours/<slug-en>" el enlace "Español" apuntaba a
    // "/es/tours/<slug-en>" -- 404. El cálculo se fue entero a
    // App\Support\LocaleAlternates (ver su docblock): en las fichas lo
    // declara el controller con el slug real de cada idioma, y en las
    // pantallas sin slug sigue siendo la misma reconstrucción de antes.
    // Acá no se arma ninguna URL: si vuelve a armarse, vuelve el defecto.
    $localeAlternateUrls = collect(app(\App\Support\LocaleAlternates::class)->urls());
@endphp
<header
    x-data="{ mobileOpen: false, scrolled: false }"
    @keydown.escape.window="mobileOpen = false"
    @scroll.window="scrolled = window.scrollY > 8"
    :class="scrolled ? 'shadow-e2' : ''"
    class="sticky top-0 z-40 border-b border-line bg-surface transition-shadow duration-300"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-6 xl:px-8">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ config('app.name') }}">
            <x-brand.mark variant="horizontal" class="h-8 w-auto" />
        </a>

        {{--
            Nav de escritorio: desde lg (1024px). Debajo de eso, drawer.
            A 1024 el conjunto logo + 6 ítems + buscador + moneda + idioma +
            botón no entra (medido: desbordaba 65px reales) — se resuelve
            cediendo el buscador (afordancia sin función todavía) hasta xl
            (1280px) y ajustando los gaps, no escondiendo moneda/idioma que
            sí son funcionales.
        --}}
        <nav class="hidden items-center gap-4 lg:flex xl:gap-6" aria-label="Principal">
            @foreach($navItems as $item)
                <a
                    href="{{ $item['route'] }}"
                    class="relative py-1 text-sm font-medium whitespace-nowrap transition-colors hover:text-action after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:rounded-full after:bg-action after:transition-transform after:duration-300 after:content-[''] {{ $item['active'] ? 'text-action after:scale-x-100' : 'text-text-2 after:scale-x-0 hover:after:scale-x-100' }}"
                    @if($item['active']) aria-current="page" @endif
                >{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-1.5 lg:flex xl:gap-2">
            {{-- Buscador: sin implementación todavía. Afordancia deshabilitada
                 y declarada como tal — no se maqueta un buscador que no busca.
                 Cede espacio primero a 1024 frente a moneda/idioma/contacto. --}}
            <button
                type="button"
                disabled
                title="{{ __('site.header.search_soon') }}"
                aria-label="{{ __('site.header.search_soon') }}"
                class="hidden h-9 w-9 items-center justify-center rounded-full text-ink-3 disabled:cursor-not-allowed disabled:opacity-50 xl:flex"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </button>

            <x-header.currency-switcher :currencies="$currencies" />

            <x-header.locale-switcher :locales="$locales" :current="$currentLocale" :alternate-urls="$localeAlternateUrls" />

            <x-ui.button href="{{ Route::has('contact') ? route('contact') : '#' }}" size="sm" class="whitespace-nowrap">
                {{ __('site.header.contact_cta') }}
            </x-ui.button>
        </div>

        {{-- Botón hamburguesa: hasta lg (1024px) --}}
        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            :aria-expanded="mobileOpen.toString()"
            aria-controls="mobile-menu"
            class="flex h-10 w-10 items-center justify-center rounded-md text-ink lg:hidden"
        >
            <span class="sr-only" x-text="mobileOpen ? '{{ __('site.header.close_menu') }}' : '{{ __('site.header.open_menu') }}'"></span>
            <svg x-show="!mobileOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
            <svg x-show="mobileOpen" x-cloak class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M6 6l12 12M6 18 18 6" />
            </svg>
        </button>
    </div>

    {{-- Drawer móvil / tablet --}}
    <div
        id="mobile-menu"
        x-show="mobileOpen"
        x-cloak
        x-transition
        class="border-t border-line bg-surface lg:hidden"
    >
        <nav class="flex flex-col gap-1 px-4 py-4" aria-label="Principal (móvil)">
            @foreach($navItems as $item)
                <a
                    href="{{ $item['route'] }}"
                    class="rounded-md px-3 py-2 text-base font-medium {{ $item['active'] ? 'bg-brand-50 text-action' : 'text-text-2 hover:bg-ground' }}"
                >{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="border-t border-line-soft px-4 py-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">{{ __('site.header.currency') }}</p>
            <div class="flex gap-2" role="group" aria-label="{{ __('site.header.currency') }}">
                @foreach($currencies as $code => $currency)
                    <button
                        type="button"
                        @click="$store.currency.set('{{ $code }}')"
                        :class="$store.currency.code === '{{ $code }}' ? 'bg-action text-on-action border-action' : 'border-line text-text-2'"
                        class="rounded-full border px-4 py-1.5 text-sm font-medium"
                    >{{ $code }}</button>
                @endforeach
            </div>
        </div>

        <div class="border-t border-line-soft px-4 py-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">{{ __('site.header.language') }}</p>
            {{-- En móvil las opciones van en línea, a la vista: un dropdown
                 aquí queda fuera del viewport del drawer sin ahorrar nada.
                 Mismas URLs alternas que el desplegable de escritorio
                 ($localeAlternateUrls, calculado arriba). --}}
            <div class="flex flex-wrap gap-2">
                @foreach($locales as $code => $label)
                    @if($code === $currentLocale)
                        <span
                            class="rounded-full border border-action px-3 py-1.5 text-sm font-medium text-action"
                            aria-current="true"
                        >{{ strtoupper(str_replace('_', '-', $code)) }}</span>
                    @else
                        <a
                            href="{{ $localeAlternateUrls[$code] ?? '#' }}"
                            class="rounded-full border border-line-soft px-3 py-1.5 text-sm font-medium text-text-2 hover:border-action hover:text-action"
                        >{{ strtoupper(str_replace('_', '-', $code)) }}</a>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="px-4 pb-4 pt-2">
            <x-ui.button href="{{ Route::has('contact') ? route('contact') : '#' }}" class="w-full justify-center">
                {{ __('site.header.contact_cta') }}
            </x-ui.button>
        </div>
    </div>
</header>
