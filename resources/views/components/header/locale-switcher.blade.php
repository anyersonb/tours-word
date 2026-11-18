@props(['locales', 'current', 'alternateUrls' => []])
{{--
    Segmento "ES" del selector PEN | ES del header (PDF s00). Un enlace real
    por cada locale ACTIVO (config('cms.active_locales')) hacia la MISMA
    pantalla; la URL la calcula x-site.header con la misma lógica que el
    hreflang de x-layout (nunca se arma aquí). PT-BR no aparece: el alcance
    del sitio es ES/EN.
--}}
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
        aria-label="{{ __('site.header.language') }}"
        class="switch__opt"
    >
        <span>{{ strtoupper(str_replace('_', '-', $current)) }}</span>
        <svg class="icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
    </button>

    <div x-show="open" x-cloak x-transition class="switch__menu" role="menu">
        @foreach($locales as $code => $label)
            @if($code === $current)
                <span class="switch__item is-current" role="menuitem" aria-current="true">
                    {{ $label }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
            @else
                <a href="{{ $alternateUrls[$code] ?? '#' }}" @click="open = false" class="switch__item" role="menuitem" lang="{{ str_replace('_', '-', $code) }}">{{ $label }}</a>
            @endif
        @endforeach
    </div>
</div>
