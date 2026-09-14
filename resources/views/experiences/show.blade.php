@php
    use App\Support\PlaceholderImage;
@endphp
{{-- noindex se queda puesto mientras el contenido sea de MUESTRA (seeder
     DemoTourSeeder); se quita cuando la clienta cargue experiencias reales. --}}
<x-layout title="{{ $experience['name'] }}" description="{{ $experience['description'] }}" :noindex="true">

    {{-- ============ MIGAS DE PAN + GALERÍA + DESCRIPCIÓN ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.experiences.index.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.experiences.show.breadcrumb_index'), 'href' => route('experiences.index')],
                ['label' => $experience['name']],
            ]" />
        </div>

        <div class="mx-auto max-w-5xl px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <x-ui.gallery :images="$experience['gallery']" :label="__('site.ui.gallery.nav_label', ['title' => $experience['name']])" />

            <h1 class="mt-6 font-display text-3xl font-semibold text-ink sm:text-4xl">{{ $experience['name'] }}</h1>
            <p class="mt-4 max-w-3xl text-base text-text-2 sm:text-lg">{{ $experience['description'] }}</p>
        </div>
    </section>

    {{-- ============ TOURS DE ESTA EXPERIENCIA ============ --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @if(!empty($relatedTours))
                <x-ui.section-title as="h2">
                    {{ __('site.experiences.show.related_tours_title', ['experience' => $experience['name']]) }}
                </x-ui.section-title>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($relatedTours as $tour)
                        <x-ui.tour-card
                            :image="$tour['images'][0]['src'] ?? PlaceholderImage::svg(480, 360, $tour['title'], '2c6fa8')"
                            :image-alt="$tour['images'][0]['alt'] ?? $tour['title']"
                            :title="$tour['title']"
                            :summary="$tour['summary']"
                            :duration="$tour['duration_label']"
                            :category="$tour['destination']['name'] ?? null"
                            :pen-cents="$tour['price_pen_cents']"
                            :usd-cents="$tour['price_usd_cents']"
                            :href="route('tours.show', $tour['slug'])"
                        />
                    @endforeach
                </div>
            @else
                <p class="text-sm text-text-2">{{ __('site.experiences.show.related_tours_empty') }}</p>
            @endif
        </div>
    </section>

    {{-- ============ CTA: VER TODOS LOS TOURS ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-8 text-center sm:px-6 lg:px-8">
            <x-ui.button variant="secondary" href="{{ route('tours.index') }}">
                {{ __('site.experiences.show.cta_all_tours') }}
            </x-ui.button>
        </div>
    </section>

</x-layout>
