@php
    use App\Support\PlaceholderImage;
@endphp
{{-- El noindex ya no se cablea aca: mientras
     config("cms.catalog_demo_content") este en true, x-layout pone
     "noindex, nofollow" en TODO el sitio publico. Cableandolo en esta
     vista, bajar la bandera dejaba los tres indices fuera del indice sin
     que nadie lo notara. --}}
<x-layout
    :title="__('site.destinations.index.meta.title')"
    :description="__('site.destinations.index.meta.description')"
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
