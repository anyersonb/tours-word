@props(['slides'])
{{--
    Hero del Home (PDF s00): slider de 3 destinos con lista lateral de
    miniaturas, flechas y barra de progreso. UNA sola implementación del
    comportamiento: Alpine.data('heroSlider') de resources/js/app.js (el mismo
    que ya usaban las fichas): autoavance pausable (hover/foco/pestaña
    oculta), quieto con prefers-reduced-motion, botón de pausa (WCAG 2.2.2).

    Las fotos de las diapositivas 2+ viven inertes en <template> y
    heroSlider() las clona al primer avance real (cero peticiones antes): la
    1.ª va eager con fetchpriority alto. Sin JS la 1.ª sigue visible.

    Los CTA apuntan a rutas reales (url_primary / url_secondary del
    controller). Textos con marcador visible ("[Texto del slide de Ica...]")
    se imprimen tal cual: es dato pendiente de la clienta.
--}}
@php
    $total = count($slides);
    // Claves nuevas pendientes en lang/ (programación): mientras no existan, respaldo.
    $tr = fn (string $key, string $fallback) => trans()->has($key) ? __($key) : $fallback;
@endphp
<section
    class="hero"
    id="hero"
    x-data="heroSlider({{ $total }}, 6000)"
    :class="{ 'is-paused': halted || paused || reducedMotion }"
    aria-roledescription="{{ $tr('home.hero.roledescription', 'carrusel') }}"
    aria-label="{{ $tr('home.hero.label', __('home.menu.destinations')) }}"
>
    <div class="hero__slides">
        @foreach ($slides as $i => $s)
            @php $img = $s['image'] ?? null; @endphp
            <article
                class="hero__slide {{ $i === 0 ? 'is-active' : '' }}"
                :class="{ 'is-active': active === {{ $i }} }"
                :aria-hidden="active === {{ $i }} ? 'false' : 'true'"
                role="group"
                aria-roledescription="diapositiva"
                aria-label="{{ $i + 1 }} / {{ $total }}"
                @if ($i !== 0) aria-hidden="true" @endif
            >
                <div class="hero__photo" data-hero-photo="{{ $i }}">
                    @if ($i === 0 && $img)
                        <x-ui.picture :src="$img" alt="" sizes="100vw" img-class="hero__bg" loading="eager" fetchpriority="high" />
                    @endif
                </div>
                @if ($i !== 0 && $img)
                    <template data-hero-template="{{ $i }}"><x-ui.picture :src="$img" alt="" sizes="100vw" img-class="hero__bg" loading="lazy" /></template>
                @endif
                <div class="hero__shade" aria-hidden="true"></div>
                <div class="container hero__copy">
                    <p class="hero__pill"><span class="hero__dot" aria-hidden="true"></span>{{ $s['badge'] }}</p>
                    @if ($i === 0)
                        <h1 class="hero__title">{{ $s['title'] }}</h1>
                    @else
                        <p class="hero__title" role="heading" aria-level="2">{{ $s['title'] }}</p>
                    @endif
                    <p class="hero__text">{{ $s['text'] }}</p>
                    <div class="hero__actions">
                        <a class="btn btn--accent btn--lg" href="{{ $s['url_primary'] ?? route('tours.index') }}" :tabindex="active === {{ $i }} ? 0 : -1" @if ($i !== 0) tabindex="-1" @endif>{{ $s['cta_primary'] }}<x-glyph name="arrow-right" :size="18" /></a>
                        <a class="btn btn--ghost btn--lg" href="{{ $s['url_secondary'] ?? route('destinations.index') }}" :tabindex="active === {{ $i }} ? 0 : -1" @if ($i !== 0) tabindex="-1" @endif>{{ $s['cta_secondary'] }}</a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="container hero__ui">
        <div class="hero__panel">
            <ol class="hero__list">
                @foreach ($slides as $i => $s)
                    <li>
                        <button
                            type="button"
                            class="hero__item {{ $i === 0 ? 'is-active' : '' }}"
                            :class="{ 'is-active': active === {{ $i }} }"
                            @click="go({{ $i }})"
                            aria-label="{{ $s['badge'] }}"
                            aria-current="{{ $i === 0 ? 'true' : 'false' }}"
                            :aria-current="active === {{ $i }} ? 'true' : 'false'"
                        >
                            <span class="hero__num" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
                            <img class="hero__thumb" src="{{ $s['thumb'] }}" alt="" width="64" height="52" loading="lazy" decoding="async">
                            <span class="hero__meta">
                                <span class="hero__name">{{ $s['name'] }}</span>
                                <span class="hero__sub">{{ $s['subtitle'] }}</span>
                            </span>
                            <span class="hero__bar" aria-hidden="true"></span>
                        </button>
                    </li>
                @endforeach
            </ol>
            <div class="hero__controls">
                <button type="button" class="hero__arrow hero__arrow--prev" @click="prev()" aria-label="{{ __('home.hero.prev') }}"><x-glyph name="arrow-left" :size="18" /></button>
                <div class="hero__progress" aria-hidden="true">
                    @foreach ($slides as $i => $s)
                        <span
                            class="hero__seg {{ $i === 0 ? 'is-active' : '' }}"
                            :class="{ 'is-active': active === {{ $i }}, 'is-done': active > {{ $i }}, 'is-reduced': reducedMotion }"
                        ><span class="hero__fill"></span></span>
                    @endforeach
                </div>
                <button type="button" class="hero__pause" @click="toggle()" :aria-pressed="(paused || reducedMotion).toString()" :aria-label="(paused || reducedMotion) ? '{{ $tr('home.hero.play', 'Reanudar carrusel') }}' : '{{ $tr('home.hero.pause', 'Pausar carrusel') }}'" aria-label="{{ $tr('home.hero.pause', 'Pausar carrusel') }}">
                    <span x-show="!(paused || reducedMotion)"><x-glyph name="pause" :size="14" /></span>
                    <span x-show="paused || reducedMotion" x-cloak><x-glyph name="play" :size="14" /></span>
                </button>
                <button type="button" class="hero__arrow hero__arrow--next" @click="next()" aria-label="{{ __('home.hero.next') }}"><x-glyph name="arrow-right" :size="18" /></button>
            </div>
            <p class="sr-only" aria-live="polite" x-text="$root.querySelectorAll('.hero__item')[active]?.getAttribute('aria-label') ?? ''"></p>
        </div>
    </div>
</section>
