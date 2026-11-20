@php
    use App\Models\Setting;

    /*
     * Header de INKA PALACE Travel (PDF s00): topbar + cinta andina + header
     * sticky. Vive en x-site.header para que TODAS las páginas lo hereden;
     * conserva lo que ya hacía el de main: rutas con route() (prefijo de
     * idioma por URL::defaults), item activo con routeIs(), selector de
     * idioma con las URLs alternas reales (LocaleAlternates), moneda PEN/USD
     * real (Alpine.store('currency')), skip-link en el layout y menú móvil
     * accesible (aria-expanded, Escape).
     */
    $navItems = [
        ['label' => __('site.nav.home'), 'route' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => __('site.nav.tours'), 'route' => route('tours.index'), 'active' => request()->routeIs('tours.*')],
        ['label' => __('site.nav.destinations'), 'route' => route('destinations.index'), 'active' => request()->routeIs('destinations.*')],
        ['label' => __('site.nav.experiences'), 'route' => route('experiences.index'), 'active' => request()->routeIs('experiences.*')],
        ['label' => __('site.nav.about'), 'route' => route('about'), 'active' => request()->routeIs('about')],
        // "Preguntas": ancla a la FAQ de Contacto (CRO B-2: una sola FAQ; la del Home
        // tiene respuestas por completar).
        ['label' => __('home.menu.faq'), 'route' => route('contact').'#preguntas', 'active' => false],
        ['label' => __('site.nav.contact'), 'route' => route('contact'), 'active' => request()->routeIs('contact*')],
    ];

    // Solo locales ACTIVOS (config('cms.active_locales')); ver LocaleAlternates
    // para el porqué de que las URLs alternas no se armen aquí.
    $activeLocales = config('cms.active_locales', []);
    $locales = collect(config('cms.locales', []))->only($activeLocales)->all();
    $currentLocale = app()->getLocale();
    $currencies = config('cms.currencies', []);
    $localeAlternateUrls = collect(app(\App\Support\LocaleAlternates::class)->urls());

    // WhatsApp/teléfono: lo cargado en Configuración (CMS) manda; si la clienta
    // aún no lo cargó, el dato del diseño (config/brand.php).
    $phone = Setting::get('contact_phone') ?: config('brand.contact.phone');
    $waDigits = preg_replace('/\D+/', '', (string) $phone);
    $waUrl = \App\Support\WhatsApp::url(__('site.whatsapp.default_text'));
@endphp

{{-- Topbar (PDF): café, 3 mensajes + aviso Camino Inca. En móvil solo el aviso. --}}
<div class="topbar">
    <div class="container topbar__inner">
        <ul class="topbar__list">
            <li class="topbar__item topbar__item--secure"><x-glyph name="shield-check" :size="15" /><span>{{ __('home.topbar.local_operator') }} <strong>{{ __('home.topbar.secure_booking') }}</strong></span></li>
            <li class="topbar__item topbar__item--resp"><x-glyph name="clock" :size="15" /><span>{{ __('home.topbar.response') }}</span></li>
            <li class="topbar__item topbar__item--wa"><x-glyph name="whatsapp" :size="15" /><span>{{ __('home.topbar.whatsapp') }} <a class="topbar__phone" href="{{ $waUrl }}" target="_blank" rel="noopener">{{ $phone }}</a></span></li>
        </ul>
        {{-- CRO M-6: el aviso lleva a la sección Camino Inca del Home. --}}
        <a class="topbar__notice" href="{{ route('home') }}#camino-inca">{{ __('home.topbar.notice') }}</a>
    </div>
</div>
<x-home.ribbon size="sm" />

<header
    class="header"
    id="inicio"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @resize.window="if (window.innerWidth >= 1100) open = false"
>
    <div class="container header__inner">
        <a class="header__logo" href="{{ route('home') }}" aria-label="{{ config('brand.name') }}">
            <x-brand.mark variant="full" sizes="(min-width: 1100px) 96px, 76px" />
        </a>

        <nav class="nav" id="menu-principal" :class="{ 'is-open': open }" aria-label="Principal">
            <ul class="nav__list">
                @foreach ($navItems as $item)
                    <li>
                        <a
                            class="nav__link {{ $item['active'] ? 'is-active' : '' }}"
                            href="{{ $item['route'] }}"
                            @if ($item['active']) aria-current="page" @endif
                            @click="open = false"
                        >{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>

            {{-- Moneda e idioma dentro del panel móvil (el selector segmentado
                 del header solo aparece desde 640px). --}}
            <div class="nav__tools">
                <div>
                    <p class="nav__tools-label">{{ __('site.header.currency') }}</p>
                    <div class="nav__pills" role="group" aria-label="{{ __('site.header.currency') }}">
                        @foreach ($currencies as $code => $currency)
                            <button type="button" class="nav__pill" @click="$store.currency.set('{{ $code }}')" :aria-pressed="($store.currency.code === '{{ $code }}').toString()" aria-pressed="false">{{ $code }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="nav__tools-label">{{ __('site.header.language') }}</p>
                    <div class="nav__pills">
                        @foreach ($locales as $code => $label)
                            @if ($code === $currentLocale)
                                <span class="nav__pill" aria-current="true">{{ strtoupper(str_replace('_', '-', $code)) }}</span>
                            @else
                                <a class="nav__pill" href="{{ $localeAlternateUrls[$code] ?? '#' }}" lang="{{ str_replace('_', '-', $code) }}">{{ strtoupper(str_replace('_', '-', $code)) }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <a class="btn btn--dark nav__cta" href="{{ route('contact') }}"><x-glyph name="message" :size="16" />{{ __('home.menu.contact_cta') }}</a>
        </nav>

        <div class="header__tools">
            <div class="switch" role="group" aria-label="{{ __('site.header.currency') }} / {{ __('site.header.language') }}">
                <x-header.currency-switcher :currencies="$currencies" />
                <x-header.locale-switcher :locales="$locales" :current="$currentLocale" :alternate-urls="$localeAlternateUrls" />
            </div>
            {{-- Pase 25: carrito con badge "1" como el PDF (reemplaza el botón de WhatsApp de CRO A-3).
                 El carrito real llega en el lote 4: por ahora es decorativo (disabled), el WhatsApp flotante cubre la conversión. --}}
            <button type="button" class="cart" disabled aria-hidden="true" tabindex="-1">
                <x-glyph name="cart" :size="20" />
                <span class="cart__count" aria-hidden="true">1</span>
            </button>
            <a class="btn btn--dark header__cta" href="{{ route('contact') }}"><x-glyph name="message" :size="16" />{{ __('home.menu.contact_cta') }}</a>
            <button
                type="button"
                class="burger"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-expanded="false"
                aria-controls="menu-principal"
                :aria-label="open ? '{{ __('site.header.close_menu') }}' : '{{ __('site.header.open_menu') }}'"
                aria-label="{{ __('site.header.open_menu') }}"
            >
                <x-glyph name="menu" :size="24" class="burger__open" />
                <x-glyph name="close" :size="24" class="burger__close" />
            </button>
        </div>
    </div>
</header>
