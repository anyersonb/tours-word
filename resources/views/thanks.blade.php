@php
    use App\Support\WhatsApp;
@endphp
{{--
    Página de gracias (CRO C-2, docs/rediseno-2026/19-cro-mobile.md). Destino
    del envío correcto del formulario de contacto: URL propia y medible como
    conversión. Siguiente paso: WhatsApp o volver a los tours. noindex: no es
    contenido, es un estado del flujo.
--}}
<x-layout :title="__('site.gracias.title')" :noindex="true" :wa-text="__('site.gracias.wa_text')">
    <section class="ip-sec thanks">
        <div class="container thanks__inner">
            <span class="thanks__icon" aria-hidden="true"><x-glyph name="check" :size="32" /></span>
            <p class="ip-eyebrow">{{ __('site.gracias.eyebrow') }}</p>
            <h1 class="sec-title thanks__title">{{ __('site.gracias.heading') }}</h1>
            <p class="sec-text thanks__text">{{ __('site.gracias.text') }}</p>

            <p class="thanks__next">{{ __('site.gracias.next') }}</p>
            <div class="thanks__actions">
                <a class="btn btn--wa btn--lg" href="{{ WhatsApp::url(__('site.gracias.wa_text')) }}" target="_blank" rel="noopener"><x-glyph name="whatsapp" :size="18" />{{ __('site.gracias.wa_cta') }}</a>
                <a class="btn btn--accent btn--lg" href="{{ route('tours.index') }}">{{ __('site.gracias.tours_cta') }}<x-glyph name="arrow-right" :size="18" /></a>
                <a class="btn btn--dark btn--lg" href="{{ route('home') }}">{{ __('site.gracias.home_cta') }}</a>
            </div>
        </div>
    </section>

    {{-- Conversión medible: URL /gracias + evento para GTM/GA4 cuando se instale (hoy no hay etiqueta). --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: 'contact_form_success' });
    </script>
</x-layout>