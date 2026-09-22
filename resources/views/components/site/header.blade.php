@php
    use App\Models\Setting;

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

    // Port Roavio (2026-09-20): franja delgada sobre el header. Mismo dato
    // que ya lee el pie (Setting, sin cablear nada) — si la clienta no ha
    // cargado teléfono/correo todavía, la franja de contacto simplemente no
    // imprime esos dos <a>; nunca un "+51 000 000 000" de relleno.
    $topbarPhone = Setting::get('contact_phone');
    $topbarEmail = Setting::get('contact_email');
    $topbarWhatsapp = filled($topbarPhone) ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $topbarPhone) : null;
    $topbarInstagram = Setting::get('social_instagram_url');
    $topbarFacebook = Setting::get('social_facebook_url');
    $iconPhoneSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>';
    $iconMailSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>';
    $iconInstagramSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6" fill="currentColor" stroke="none"/></svg>';
    $iconFacebookSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5" aria-hidden="true"><path d="M14 21v-7h3l1-4h-4V7.5A1.5 1.5 0 0 1 15.5 6H18V3h-3a4.5 4.5 0 0 0-4.5 4.5V10H8v4h2.5v7Z"/></svg>';
    $hasTopbarContact = filled($topbarPhone) || filled($topbarEmail);
    $hasTopbarSocial = filled($topbarInstagram) || filled($topbarFacebook);
@endphp

{{--
    Topbar — port Roavio (2026-09-20): franja delgada, oscura, sobre el
    header blanco. Roavio la usa para un mensaje de bienvenida + contacto +
    idioma; acá va la tagline + los mismos datos reales de contacto que ya
    imprime el pie, sin duplicar el selector de idioma (ese ya vive en el
    header principal, no hace falta un segundo). Se oculta entera si no hay
    NINGÚN dato de contacto/social — nunca una franja vacía con solo la
    tagline flotando sin nada al lado.
--}}
@if($hasTopbarContact || $hasTopbarSocial)
    <div class="hidden bg-ink-surface text-xs text-on-dark-2 lg:block">
        <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-6 xl:px-8">
            <p class="truncate">{{ __('site.header.topbar_tagline') }}</p>

            <div class="flex shrink-0 items-center gap-4">
                @if(filled($topbarPhone))
                    <a href="{{ $topbarWhatsapp ?? 'tel:'.preg_replace('/\s+/', '', $topbarPhone) }}" class="inline-flex items-center gap-1.5 transition-colors hover:text-white">
                        {!! $iconPhoneSmall !!}<span>{{ $topbarPhone }}</span>
                    </a>
                @endif
                @if(filled($topbarEmail))
                    <a href="mailto:{{ $topbarEmail }}" class="inline-flex items-center gap-1.5 transition-colors hover:text-white">
                        {!! $iconMailSmall !!}<span>{{ $topbarEmail }}</span>
                    </a>
                @endif
                @if($hasTopbarSocial)
                    <span class="flex items-center gap-3 border-l border-on-dark-line pl-4" aria-label="{{ __('site.contacto.info.social_title') }}">
                        @if(filled($topbarInstagram))
                            <a href="{{ $topbarInstagram }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_instagram') }}" class="transition-colors hover:text-white">{!! $iconInstagramSmall !!}</a>
                        @endif
                        @if(filled($topbarFacebook))
                            <a href="{{ $topbarFacebook }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_facebook') }}" class="transition-colors hover:text-white">{!! $iconFacebookSmall !!}</a>
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif

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
