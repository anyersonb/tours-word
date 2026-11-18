@props(['tour'])
{{--
    Tarjeta de tour del Home (PDF s03). Datos del CMS (title, destino,
    duración, precio, foto, url). Lo que el modelo NO tiene (rating, reseñas,
    idiomas) se imprime como MARCADOR visible; nunca una cifra inventada. El
    precio sale en las dos monedas (selector PEN/USD del header) cuando hay
    centavos de ambas; si falta alguna, se muestra el texto formateado por
    Money en la moneda por defecto, y sin precio, el marcador [PRECIO].
--}}
@php
    $tones = ['Recomendado' => 'forest', 'Tendencia' => 'teal', 'Más vendido' => 'accent', 'Últimos cupos' => 'alert'];
    $tone = $tones[$tour['badge'] ?? ''] ?? 'forest';
    $src = $tour['image'] ?? null;
    $pen = $tour['price_pen_cents'] ?? null;
    $usd = $tour['price_usd_cents'] ?? null;
    $url = $tour['url'] ?? route('tours.index');
@endphp
<article class="tour-card">
    <div class="tour-card__media">
        @if ($src)
            <x-ui.picture :src="$src" :alt="$tour['image_alt'] ?? $tour['title']" sizes="(min-width: 1024px) 24vw, (min-width: 640px) 46vw, 82vw" img-class="" />
        @else
            <x-glyph name="mountain" :size="48" class="tour-card__ph" />
        @endif
        @if (!empty($tour['sample']))<span class="sample-chip sample-chip--br">Muestra</span>@endif
        @if (!empty($tour['badge']))
            <span class="badge badge--{{ $tone }}">{{ $tour['badge'] === 'Más vendido' ? '★ ' : '' }}{{ $tour['badge'] }}</span>
        @endif
        <button type="button" class="tour-card__fav" x-data="{ on: false }" @click="on = !on" :aria-pressed="on.toString()" aria-pressed="false" aria-label="{{ __('home.featured.favorite') }}: {{ $tour['title'] }}"><x-glyph name="heart" :size="16" /></button>
        @if (!empty($tour['destination_name']))
            <span class="ip-chip-dark"><x-glyph name="map-pin" :size="13" />{{ $tour['destination_name'] }}</span>
        @endif
    </div>
    <div class="tour-card__body">
        <p class="rating"><span class="rating__stars" aria-hidden="true">@for ($i = 0; $i < 5; $i++)<x-glyph name="star" :size="11" />@endfor</span><strong>{{ __('home.featured.rating_placeholder') }}</strong><span class="rating__n">({{ __('home.featured.reviews_placeholder') }})</span></p>
        <h3 class="tour-card__title">{{ $tour['title'] }}</h3>
        <ul class="tour-card__meta">
            <li class="ip-chip"><x-glyph name="clock" :size="13" />{{ $tour['duration_label'] ?? __('home.featured.duration_placeholder') }}</li>
            <li class="ip-chip"><x-glyph name="globe" :size="13" />{{ $tour['languages'] ?? __('home.featured.languages_placeholder') }}</li>
        </ul>
        <p class="tour-card__cancel"><x-glyph name="check" :size="14" />{{ __('home.featured.flexible_cancellation') }}</p>
        <div class="tour-card__foot">
            <p class="price"><span class="price__from">{{ __('home.featured.from') }}</span>
                <strong class="price__amount">
                    @if ($pen && $usd)
                        <x-ui.money :pen-cents="$pen" :usd-cents="$usd" />
                    @elseif (!empty($tour['price']))
                        {{ $tour['price'] }}
                    @else
                        {{ str_contains($ph = __('home.featured.price_placeholder'), 'S/') ? $ph : 'S/ '.$ph }}
                    @endif
                </strong>
                <span class="price__per">{{ __('home.featured.per_person') }}</span></p>
            <a class="btn btn--accent btn--sm" href="{{ $url }}" aria-label="{{ __('home.featured.view_tour') }}: {{ $tour['title'] }}">{{ __('home.featured.view_tour') }}</a>
        </div>
    </div>
</article>
