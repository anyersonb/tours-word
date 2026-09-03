@props(['currencies'])
<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open.toString()"
        class="flex h-9 items-center gap-1 rounded-full border border-line px-3 text-sm font-medium text-text-2 hover:border-action hover:text-action"
    >
        <span x-text="$store.currency.code">PEN</span>
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    {{-- El z-index vive en el PANEL, no en la barra: si se sube el header
         entero, tapa el contenido al hacer scroll. --}}
    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-50 mt-2 w-40 overflow-hidden rounded-lg border border-line bg-surface shadow-lg"
        role="menu"
    >
        @foreach($currencies as $code => $currency)
            <button
                type="button"
                role="menuitem"
                @click="$store.currency.set('{{ $code }}'); open = false"
                class="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-ground"
                :class="$store.currency.code === '{{ $code }}' ? 'font-semibold text-action' : 'text-text-2'"
            >
                <span>{{ $code }}</span>
                <span class="text-text-muted">{{ $currency['symbol'] }}</span>
            </button>
        @endforeach
    </div>
</div>
