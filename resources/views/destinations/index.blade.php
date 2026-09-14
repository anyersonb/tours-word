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

    {{-- Cabecera sin foto: las fotos de cabecera disponibles son 3 y ya las
         usan la Home y el indice de tours. Repetir una aca la gastaria sin
         aportar nada; la banda calida cumple el mismo papel de separar la
         cabecera del catalogo. --}}
    <x-ui.page-header
        :breadcrumbs="[
            ['label' => __('site.destinations.index.breadcrumb.home'), 'href' => route('home')],
            ['label' => __('site.destinations.index.breadcrumb.current')],
        ]"
        :title="__('site.destinations.index.hero.title')"
        :lead="__('site.destinations.index.hero.subtitle')"
    />

    {{-- ============ REJILLA ============ --}}
    <section class="bg-surface">
        <div class="shell section">
            @if(empty($destinations))
                <x-ui.empty-state>{{ __('site.destinations.index.empty') }}</x-ui.empty-state>
            @else
                {{-- auto-fit con 1fr: las tarjetas llenan la fila. El tope de
                     280px anterior las dejaba encogidas y centradas, con un
                     hueco de ~170px a la izquierda del contenedor. --}}
                <div class="grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
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
