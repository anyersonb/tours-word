@php
    use App\Support\PlaceholderImage;
@endphp
{{-- noindex se queda puesto mientras el contenido sea de MUESTRA (seeder
     DemoTourSeeder); se quita cuando la clienta cargue destinos reales. --}}
<x-layout title="{{ $destination['name'] }}" description="{{ $destination['description'] }}" :noindex="true">

    {{-- ============ MIGAS DE PAN + GALERÍA + DESCRIPCIÓN ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.destinations.index.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.destinations.show.breadcrumb_index'), 'href' => route('destinations.index')],
                ['label' => $destination['name']],
            ]" />
        </div>

        <div class="mx-auto max-w-5xl px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <x-ui.gallery :images="$destination['gallery']" :label="__('site.ui.gallery.nav_label', ['title' => $destination['name']])" />

            {{-- Objetivo 2 (lote i18n): lang="es" honesto cuando este
                 destino todavia no tiene su traduccion al locale de la URL
                 -- ver ResolvesBySlugByLocale. --}}
            @php($fallbackLangAttr = $contentFallbackLocale ? str_replace('_', '-', $contentFallbackLocale) : null)

            <h1 class="mt-6 font-display text-3xl font-semibold text-ink sm:text-4xl" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $destination['name'] }}</h1>
            <x-ui.content-fallback-notice :locale="$contentFallbackLocale" />
            <p class="mt-4 max-w-3xl text-base text-text-2 sm:text-lg" @if($fallbackLangAttr) lang="{{ $fallbackLangAttr }}" @endif>{{ $destination['description'] }}</p>
        </div>
    </section>

    {{-- ============ TOURS EN ESTE DESTINO ============ --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @if(!empty($relatedTours))
                <x-ui.section-title as="h2">
                    {{ __('site.destinations.show.related_tours_title', ['destination' => $destination['name']]) }}
                </x-ui.section-title>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($relatedTours as $tour)
                        <x-ui.tour-card
                            :image="$tour['images'][0]['src'] ?? PlaceholderImage::svg(480, 360, $tour['title'], '2c6fa8')"
                            :image-alt="$tour['images'][0]['alt'] ?? $tour['title']"
                            :title="$tour['title']"
                            :summary="$tour['summary']"
                            :duration="$tour['duration_label']"
                            :category="$tour['experiences'][0]['name'] ?? null"
                            :pen-cents="$tour['price_pen_cents']"
                            :usd-cents="$tour['price_usd_cents']"
                            :href="route('tours.show', $tour['slug'])"
                        />
                    @endforeach
                </div>
            @else
                <p class="text-sm text-text-2">{{ __('site.destinations.show.related_tours_empty') }}</p>
            @endif
        </div>
    </section>

    {{-- ============ CTA: VER TODOS LOS TOURS ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-8 text-center sm:px-6 lg:px-8">
            <x-ui.button variant="secondary" href="{{ route('tours.index') }}">
                {{ __('site.destinations.show.cta_all_tours') }}
            </x-ui.button>
        </div>
    </section>

</x-layout>
