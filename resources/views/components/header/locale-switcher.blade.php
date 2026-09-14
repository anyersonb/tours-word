@props(['locales', 'current', 'alternateUrls' => []])
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
        Objetivo (lote i18n, 2026-09-14): un enlace real por cada locale
        activo (config('cms.active_locales')), apuntando a la MISMA pantalla
        que se está viendo. La URL destino la calcula x-site.header con la
        MISMA lógica que el hreflang de x-layout (App\Support\Locale +
        route() con los parámetros de la request actual) -- nunca se arma
        acá para no divergir de ese hreflang.

        PT-BR no aparece: $locales ya llega filtrado a solo los locales
        activos (el alcance del sitio es ES/EN, decisión de Anyerson del
        2026-09-10) -- no se promete un idioma que no existe.
    --}}
    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-lg border border-line bg-surface shadow-lg"
        role="menu"
    >
        @foreach($locales as $code => $label)
            @if($code === $current)
                <span class="flex items-center justify-between px-4 py-2.5 text-sm font-semibold text-action" role="menuitem" aria-current="true">
                    {{ $label }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
            @else
                <a
                    href="{{ $alternateUrls[$code] ?? '#' }}"
                    @click="open = false"
                    class="flex items-center justify-between px-4 py-2.5 text-sm text-text-2 hover:bg-ground hover:text-action"
                    role="menuitem"
                >
                    {{ $label }}
                </a>
            @endif
        @endforeach
    </div>
</div>
