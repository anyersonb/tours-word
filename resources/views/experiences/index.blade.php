@php
    use App\Support\PlaceholderImage;

    // Mismos iconos y mismas claves (slug) que ya usa home.blade.php para no
    // introducir un segundo mapa de iconos de experiencia.
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
    :title="__('site.experiences.index.meta.title')"
    :description="__('site.experiences.index.meta.description')"
    :noindex="true"
>

    <x-ui.page-header
        :breadcrumbs="[
            ['label' => __('site.experiences.index.breadcrumb.home'), 'href' => route('home')],
            ['label' => __('site.experiences.index.breadcrumb.current')],
        ]"
        :title="__('site.experiences.index.hero.title')"
        :lead="__('site.experiences.index.hero.subtitle')"
    />

    {{-- ============ REJILLA ============ --}}
    <section class="bg-surface">
        <div class="shell section">
            @if(empty($experiences))
                <x-ui.empty-state>{{ __('site.experiences.index.empty') }}</x-ui.empty-state>
            @else
                <div class="grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),1fr))]">
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
