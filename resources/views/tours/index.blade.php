@php
    use App\Support\PlaceholderImage;
@endphp
{{-- noindex se queda puesto mientras el contenido sea de MUESTRA (seeder
     DemoTourSeeder); se quita cuando la clienta cargue tours reales. --}}
<x-layout
    title="{{ __('site.tours.index.meta.title') }}"
    description="{{ __('site.tours.index.meta.description') }}"
    :noindex="true"
>

    {{-- ============ MIGAS DE PAN + TÍTULO ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.tours.index.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.tours.index.breadcrumb.current')],
            ]" />
        </div>

        <div class="mx-auto max-w-7xl px-4 pb-8 pt-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-3xl font-semibold text-ink sm:text-4xl">
                {{ __('site.tours.index.hero.title') }}
            </h1>
            <p class="mt-3 max-w-2xl text-base text-text-2 sm:text-lg">
                {{ __('site.tours.index.hero.subtitle') }}
            </p>
        </div>
    </section>

    {{-- ============ FILTROS + REJILLA ============ --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <form
                method="GET"
                action="{{ route('tours.index') }}"
                class="mb-8 flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5 sm:flex-row sm:flex-wrap sm:items-end"
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

                <x-ui.button type="submit">
                    {{ __('site.tours.index.filters.submit') }}
                </x-ui.button>

                @if($filters['destino'] || $filters['experiencia'])
                    <x-ui.button variant="ghost" href="{{ route('tours.index') }}">
                        {{ __('site.tours.index.filters.clear') }}
                    </x-ui.button>
                @endif
            </form>

            @if($tours->isEmpty())
                <p class="rounded-2xl border border-dashed border-line bg-surface p-8 text-center text-sm text-text-2">
                    {{ __('site.tours.index.empty') }}
                </p>
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
                            :pen-cents="$tour['price_pen_cents']"
                            :usd-cents="$tour['price_usd_cents']"
                            :href="route('tours.show', $tour['slug'])"
                        />
                    @endforeach
                </div>

                <x-ui.pagination :paginator="$tours" />
            @endif
        </div>
    </section>

</x-layout>
