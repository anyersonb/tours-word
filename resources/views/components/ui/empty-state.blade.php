@props([
    'icon' => null,
])
{{--
    Estado vacío del catálogo. Antes, cuando una colección venía vacía, la
    sección entera desaparecía: la página quedaba con un hueco sin explicar y
    el visitante no sabía si había fallado algo. El copy ya existía en
    lang/{es,en}/site.php (home.empty.*) sin que nada lo usara.
--}}
<div {{ $attributes->class(['flex flex-col items-center gap-3 rounded-panel border border-dashed border-line bg-surface/60 px-6 py-12 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-action" aria-hidden="true">
        @if($icon)
            {!! $icon !!}
        @else
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 8c0-3 3.5-5 8-5s8 2 8 5-3.5 9-8 9-8-6-8-9Z"/><path d="M12 17v4"/></svg>
        @endif
    </span>
    <p class="max-w-sm text-sm text-text-2">{{ $slot }}</p>
</div>
