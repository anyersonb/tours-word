@props(['text' => null, 'lift' => false])
{{--
    WhatsApp flotante en TODAS las páginas (CRO A-2, docs/rediseno-2026/19-cro-mobile.md).
    Lo monta x-layout. El mensaje va prellenado (?text=) según la página: la
    ficha de tour pasa el nombre del tour. Loop back 1 (BUG-01/02): círculo sin píldora; setupWaFloat() (app.js) lo esconde
    mientras cubra footer, panel de reserva, CTAs del hero o formularios.
    $lift = true sube el botón sobre la
    barra sticky de reserva de la ficha (móvil), para no solaparla.
--}}
@php
    $waText = filled($text) ? $text : __('site.whatsapp.default_text');
    $waLabel = __('site.whatsapp.label');
    // Burbuja del PDF (s01). Claves nuevas pendientes en lang/ (programación): mientras no existan, respaldo por idioma.
    $bubbleTitle = trans()->has('site.whatsapp.bubble_title') ? __('site.whatsapp.bubble_title') : (app()->getLocale() === 'en' ? 'Need help?' : '¿Te ayudamos?');
    $bubbleText = trans()->has('site.whatsapp.bubble_text') ? __('site.whatsapp.bubble_text') : (app()->getLocale() === 'en' ? 'Message us on WhatsApp' : 'Escríbenos por WhatsApp');
@endphp
<a class="wa-float {{ $lift ? 'wa-float--lift' : '' }}" href="{{ \App\Support\WhatsApp::url($waText) }}" target="_blank" rel="noopener" aria-label="{{ $waLabel }}" title="{{ $waLabel }}" data-wa-float>
    <span class="wa-float__bubble" aria-hidden="true"><strong>{{ $bubbleTitle }}</strong><span>{{ $bubbleText }}</span></span>
    <span class="wa-float__btn"><x-glyph name="whatsapp" :size="30" /></span>
</a>
