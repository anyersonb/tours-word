@php
    use App\Support\PlaceholderImage;

    // Mismos íconos y mismas claves (slug) que ya usa home.blade.php para no
    // introducir un segundo mapa de íconos de experiencia.
    $experienceIcons = [
        'trekking' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="m3 20 6-10 4 6 2-3 6 7H3Z"/></svg>',
        'gastronomia' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M6 3v7a2 2 0 0 0 2 2 2 2 0 0 0 2-2V3M8 12v9M17 3c-1.5 0-3 1.5-3 4s1.5 4 3 4v9"/></svg>',
        'cultura' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M4 8c0-3 3.5-5 8-5s8 2 8 5-3.5 9-8 9-8-6-8-9Z"/><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><path d="M9 13c1 1 5 1 6 0"/></svg>',
    ];
    $defaultExperienceIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-2 6-6 2 2-6 6-2Z"/></svg>';
@endphp
{{-- noindex se queda puesto mientras el contenido sea de MUESTRA (seeder
     DemoTourSeeder); se quita cuando la clienta cargue experiencias reales. --}}
<x-layout
    title="{{ __('site.experiences.index.meta.title') }}"
    description="{{ __('site.experiences.index.meta.description') }}"
    :noindex="true"
>

    {{-- ============ MIGAS DE PAN + TÍTULO ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.experiences.index.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.experiences.index.breadcrumb.current')],
            ]" />
        </div>

        <div class="mx-auto max-w-7xl px-4 pb-8 pt-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-3xl font-semibold text-ink sm:text-4xl">
                {{ __('site.experiences.index.hero.title') }}
            </h1>
            <p class="mt-3 max-w-2xl text-base text-text-2 sm:text-lg">
                {{ __('site.experiences.index.hero.subtitle') }}
            </p>
        </div>
    </section>

    {{-- ============ REJILLA ============ --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            @if(empty($experiences))
                <p class="rounded-2xl border border-dashed border-line bg-surface p-8 text-center text-sm text-text-2">
                    {{ __('site.experiences.index.empty') }}
                </p>
            @else
                <div class="grid justify-center gap-6 [grid-template-columns:repeat(auto-fit,minmax(200px,260px))]">
                    @foreach($experiences as $experience)
                        <x-ui.experience-card
                            :image="$experience['gallery'][0]['src'] ?? PlaceholderImage::svg(480, 360, $experience['name'], '2c6fa8')"
                            :image-alt="$experience['cover_image_alt'] ?? $experience['name']"
                            :title="$experience['name']"
                            :description="$experience['description']"
                            :icon="$experienceIcons[$experience['slug']] ?? $defaultExperienceIcon"
                            :href="route('experiences.show', $experience['slug'])"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

</x-layout>
