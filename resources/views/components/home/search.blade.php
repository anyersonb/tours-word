@props(['destinations' => []])
{{--
    Buscador (PDF s00). Hoy el catálogo solo filtra por destino (?destino=<slug>
    exacto, ver TourController@index), así que:
      - "Destino" es un <select> con los destinos REALES publicados (slug del
        idioma actual) y envía GET a tours.index: funciona de verdad.
      - "Fecha" y "Viajeros" se maquetan como en el diseño pero NO se envían
        (sin atributo name): no existe motor de reservas hasta el lote 4. Se
        declara aquí para que nadie los cablee a ciegas.
    Etiqueta con for/id, campos de 16px (evita el zoom de iOS).
--}}
@php $tr = fn (string $key, string $fallback) => trans()->has($key) ? __($key) : $fallback; @endphp
<div class="container search-wrap">
    <form class="search" role="search" method="get" action="{{ route('tours.index') }}" aria-label="{{ __('home.search.submit') }}">
        <div class="search__field">
            <label class="search__label" for="s-destino">{{ __('home.search.destination') }}</label>
            <span class="search__control"><x-glyph name="map-pin" :size="16" />
                <select id="s-destino" name="destino">
                    <option value="">{{ __('home.search.destination_placeholder') }}</option>
                    @foreach ($destinations as $d)
                        <option value="{{ $d['slug'] }}">{{ $d['name'] }}</option>
                    @endforeach
                </select>
            </span>
        </div>
        <div class="search__field">
            <label class="search__label" for="s-fecha">{{ __('home.search.date') }}</label>
            <span class="search__control"><x-glyph name="calendar" :size="16" /><input id="s-fecha" x-data="{ kind: 'text' }" :type="kind" @focus="kind = 'date'" @blur="if (! $el.value) kind = 'text'" type="text" placeholder="{{ __('home.search.date_placeholder') }}" autocomplete="off"></span>
        </div>
        <div class="search__field">
            <label class="search__label" for="s-viajeros">{{ __('home.search.travelers') }}</label>
            <span class="search__control"><x-glyph name="users" :size="16" />
                <select id="s-viajeros">
                    @foreach (range(1, 12) as $n)
                        <option value="{{ $n }}" @selected($n === 2)>{{ $n }} {{ $n === 1 ? $tr('home.search.traveler_one', 'viajero') : $tr('home.search.traveler_other', 'viajeros') }}</option>
                    @endforeach
                </select>
            </span>
        </div>
        <button type="submit" class="btn btn--accent btn--lg search__btn"><x-glyph name="search" :size="18" />{{ __('home.search.submit') }}</button>
    </form>
</div>
