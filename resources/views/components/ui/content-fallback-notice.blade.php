@props(['locale'])
{{--
    Objetivo 2 (lote i18n, 2026-09-14). Se renderiza SOLO cuando el
    controller detecto que el contenido principal (title/name) de la ficha
    no tiene traduccion propia para el locale de la URL -- ver
    ResolvesBySlugByLocale::isContentFallbackFor() y el docblock de esa
    clase. El texto del aviso va en el idioma ACTUAL de la pagina (no lleva
    lang propio); el contenido que cae al idioma de respaldo se marca aparte
    con el atributo lang="{{ $locale }}" en el bloque que lo envuelve, para
    nunca fingir que ese texto esta en el idioma de la URL.
--}}
@if($locale)
    <p role="note" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-line-soft bg-surface-2 px-3 py-2 text-xs text-text-muted">
        {{ __('site.ui.content_fallback_notice', ['language' => config('cms.locales.'.app()->getLocale())]) }}
    </p>
@endif
