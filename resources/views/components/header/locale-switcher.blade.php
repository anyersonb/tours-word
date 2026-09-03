@props(['locales', 'activeLocales', 'current'])
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
        <span>{{ strtoupper(str_replace('_', '-', $current)) }}</span>
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    {{--
        Lugar reservado para EN / PT-BR (lote 5). Hoy solo ES está activo
        (config('cms.active_locales')), así que las otras dos opciones se
        muestran deshabilitadas con su motivo — no son enlaces rotos a /en
        o /pt-br, que todavía no existen.
    --}}
    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-lg border border-line bg-surface shadow-lg"
        role="menu"
    >
        @foreach($locales as $code => $label)
            @php $isActive = in_array($code, $activeLocales, true); @endphp
            @if($isActive)
                <span class="flex items-center justify-between px-4 py-2.5 text-sm font-semibold text-action" role="menuitem" aria-current="true">
                    {{ $label }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
            @else
                <span
                    class="flex cursor-not-allowed items-center justify-between px-4 py-2.5 text-sm text-text-muted opacity-60"
                    role="menuitem"
                    aria-disabled="true"
                    title="{{ __('site.header.language_soon') }}"
                >
                    {{ $label }}
                    <span class="text-xs">{{ __('site.header.language_soon') }}</span>
                </span>
            @endif
        @endforeach
    </div>
</div>
