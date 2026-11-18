@props(['currencies'])
{{-- Segmento "PEN" del selector PEN | ES del header (PDF s00). Moneda REAL:
     escribe en Alpine.store('currency') (persistido en localStorage), que lee
     cada <x-ui.money> de la página. --}}
<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="switch__seg"
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-expanded="false"
        aria-haspopup="true"
        aria-label="{{ __('site.header.currency') }}"
        class="switch__opt is-active"
    >
        <span x-text="$store.currency.code">PEN</span>
        <svg class="icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
    </button>

    {{-- El z-index vive en el PANEL, no en la barra. --}}
    <div x-show="open" x-cloak x-transition class="switch__menu" role="menu">
        @foreach($currencies as $code => $currency)
            <button
                type="button"
                role="menuitemradio"
                @click="$store.currency.set('{{ $code }}'); open = false"
                :aria-checked="($store.currency.code === '{{ $code }}').toString()"
                :class="$store.currency.code === '{{ $code }}' ? 'is-current' : ''"
                class="switch__item"
            >
                <span>{{ $code }}</span>
                <small>{{ $currency['symbol'] }}</small>
            </button>
        @endforeach
    </div>
</div>
