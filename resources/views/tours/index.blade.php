@php
    use App\Support\PlaceholderImage;
@endphp
{{-- noindex se queda puesto mientras el contenido sea de MUESTRA (seeder
     DemoTourSeeder); se quita cuando la clienta cargue tours reales. --}}
<x-layout
    :title="__('site.tours.index.meta.title')"
    :description="__('site.tours.index.meta.description')"
    :noindex="true"
>

    {{-- ============ CABECERA ============
         Con foto: el indice de tours es la pantalla comercial del sitio y
         merece el mismo peso visual que la Home. La foto es de banco y
         temporal, servida como asset (cromo del sitio), no por /storage. --}}
    <x-ui.page-header
        :breadcrumbs="[
            ['label' => __('site.tours.index.breadcrumb.home'), 'href' => route('home')],
            ['label' => __('site.tours.index.breadcrumb.current')],
        ]"
        :title="__('site.tours.index.hero.title')"
        :lead="__('site.tours.index.hero.subtitle')"
        :image="asset('images/site/hero-cordillera-rio.jpg')"
        image-alt=""
        position="center 55%"
    />

    {{-- ============ FILTROS + REJILLA ============ --}}
    <section class="bg-surface">
        <div class="shell section">
            {{-- La barra de filtros se apoya sobre el borde superior de la
                 seccion (margen negativo + z-index propio): se lee como un
                 control de la cabecera y no como una caja suelta flotando en
                 medio del blanco. --}}
            <form
                method="GET"
                action="{{ route('tours.index') }}"
                class="relative z-10 -mt-[calc(var(--section-y)+2.25rem)] mb-10 flex flex-col gap-4 rounded-panel border border-line bg-surface p-5 shadow-e3 sm:flex-row sm:flex-wrap sm:items-end sm:p-6"
            >
                <div class="sm:w-56">
                    <x-ui.form.select
                        name="destino"
                        :label="__('site.tours.index.filters.destination_label')"
                        :placeholder="__('site.tours.index.filters.destination_placeholder')"
                        :options="collect($destinationOptions)->pluck('name', 'slug')->all()"
                        :value="$filters['destino']"
                    />
                </div>

                <div class="sm:w-56">
                    <x-ui.form.select
                        name="experiencia"
                        :label="__('site.tours.index.filters.experience_label')"
                        :placeholder="__('site.tours.index.filters.experience_placeholder')"
                        :options="collect($experienceOptions)->pluck('name', 'slug')->all()"
                        :value="$filters['experiencia']"
                    />
                </div>

                <x-ui.button type="submit" class="px-6 py-2.5">
                    {{ __('site.tours.index.filters.submit') }}
                </x-ui.button>

                @if($filters['destino'] || $filters['experiencia'])
                    <x-ui.button variant="ghost" href="{{ route('tours.index') }}">
                        {{ __('site.tours.index.filters.clear') }}
                    </x-ui.button>
                @endif
            </form>

            @if($tours->isEmpty())
                <x-ui.empty-state>{{ __('site.tours.index.empty') }}</x-ui.empty-state>
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($tours as $tour)
                        <x-ui.tour-card
                            :image="$tour['images'][0]['src'] ?? PlaceholderImage::svg(480, 360, $tour['title'], '2c6fa8')"
                            :image-alt="$tour['images'][0]['alt'] ?? $tour['title']"
                            :title="$tour['title']"
                            :summary="$tour['summary']"
                            :duration="$tour['duration_label']"
                            :category="$tour['experiences'][0]['name'] ?? null"
                            :location="$tour['destination']['name'] ?? null"
                            :pen-cents="$tour['price_pen_cents']"
                            :usd-cents="$tour['price_usd_cents']"
                            :href="route('tours.show', $tour['slug'])"
                            sizes="(min-width: 1024px) 24rem, (min-width: 640px) 45vw, 92vw"
                        />
                    @endforeach
                </div>

                <x-ui.pagination :paginator="$tours" />
            @endif
        </div>
    </section>

</x-layout>
