@php
    use App\Support\PlaceholderImage;
@endphp
{{-- noindex se queda puesto mientras el contenido sea de MUESTRA (seeder
     DemoTourSeeder); se quita cuando la clienta cargue destinos reales. --}}
<x-layout
    :title="__('site.destinations.index.meta.title')"
    :description="__('site.destinations.index.meta.description')"
    :noindex="true"
>

    {{-- ============ MIGAS DE PAN + TÍTULO ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.destinations.index.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.destinations.index.breadcrumb.current')],
            ]" />
        </div>

        <div class="mx-auto max-w-7xl px-4 pb-8 pt-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-3xl font-semibold text-ink sm:text-4xl">
                {{ __('site.destinations.index.hero.title') }}
            </h1>
            <p class="mt-3 max-w-2xl text-base text-text-2 sm:text-lg">
                {{ __('site.destinations.index.hero.subtitle') }}
            </p>
        </div>
    </section>

    {{-- ============ REJILLA ============ --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            @if(empty($destinations))
                <p class="rounded-2xl border border-dashed border-line bg-surface p-8 text-center text-sm text-text-2">
                    {{ __('site.destinations.index.empty') }}
                </p>
            @else
                <div class="grid justify-center gap-4 [grid-template-columns:repeat(auto-fit,minmax(220px,280px))]">
                    @foreach($destinations as $i => $destination)
                        <x-ui.destination-card
                            :image="$destination['gallery'][0]['src'] ?? PlaceholderImage::svg(480, 600, $destination['name'], '2c6fa8')"
                            :image-alt="$destination['cover_image_alt'] ?? $destination['name']"
                            :name="$destination['name']"
                            :tagline="$destination['description']"
                            :href="route('destinations.show', $destination['slug'])"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

</x-layout>
