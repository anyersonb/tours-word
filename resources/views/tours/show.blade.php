@php
    use App\Support\PlaceholderImage;

    $galleryImages = collect($tour['images'])->map(fn ($image) => ['src' => $image['src'], 'alt' => $image['alt']])->all();
    if (empty($galleryImages)) {
        $galleryImages = [['src' => PlaceholderImage::svg(1200, 800, $tour['title'], '2c6fa8'), 'alt' => $tour['title']]];
    }

    // Íconos: mismo lenguaje visual (stroke-width 2, viewBox 24x24) que el
    // resto del sitio (home/nosotros/contacto), para no introducir un
    // segundo estilo.
    $iconCheck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0 text-action" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $iconX = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 shrink-0 text-text-muted" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>';
    $iconGauge = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="m12 14 3-5"/><circle cx="12" cy="14" r="1"/><path d="M4 15a8 8 0 1 1 16 0"/></svg>';
    $iconPin = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
@endphp
<x-layout title="{{ $tour['title'] }}" description="{{ $tour['summary'] }}" :noindex="true">

    {{-- ============ MIGAS DE PAN + GALERÍA + PRECIO/CTA ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.tours.index.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.tours.show.breadcrumb_index'), 'href' => route('tours.index')],
                ['label' => $tour['title']],
            ]" />
        </div>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[3fr_2fr] lg:items-start lg:px-8 lg:py-12">
            <div>
                <x-ui.gallery :images="$galleryImages" :label="__('site.ui.gallery.nav_label', ['title' => $tour['title']])" />

                <h1 class="mt-6 font-display text-3xl font-semibold text-ink sm:text-4xl">{{ $tour['title'] }}</h1>

                @if($tour['destination'])
                    <a href="{{ route('destinations.show', $tour['destination']['slug']) }}" class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-brand-text hover:underline">
                        {!! $iconPin !!}{{ $tour['destination']['name'] }}
                    </a>
                @endif

                <p class="mt-4 text-base text-text-2 sm:text-lg">{{ $tour['summary'] }}</p>

                <div class="mt-5 flex flex-wrap gap-x-6 gap-y-3 text-sm text-text-2">
                    @if($tour['duration_label'])
                        <span class="inline-flex items-center gap-1.5">
                            {!! $iconClock !!}
                            <strong class="font-medium text-ink">{{ __('site.tours.show.duration_label') }}:</strong> {{ $tour['duration_label'] }}
                        </span>
                    @endif
                    @if($tour['difficulty'])
                        <span class="inline-flex items-center gap-1.5">
                            {!! $iconGauge !!}
                            <strong class="font-medium text-ink">{{ __('site.tours.show.difficulty_label') }}:</strong> {{ __('tours.difficulty.'.$tour['difficulty']) }}
                        </span>
                    @endif
                </div>

                <div class="mt-8 max-w-2xl text-base leading-relaxed text-text-2">
                    {{ $tour['description'] }}
                </div>
            </div>

            <div class="lg:sticky lg:top-24">
                <div class="rounded-3xl border border-line bg-surface p-6 shadow-sm">
                    <x-ui.money
                        :pen-cents="$tour['price_pen_cents']"
                        :usd-cents="$tour['price_usd_cents']"
                        :prefix="__('site.tours.show.price_prefix')"
                        class="font-display text-2xl font-semibold text-ink"
                    />

                    <x-ui.button href="{{ route('contact') }}" class="mt-5 w-full justify-center">
                        {{ __('site.tours.show.cta_reserve') }}
                    </x-ui.button>

                    @if($tour['meeting_point'])
                        <div class="mt-6 border-t border-line-soft pt-5">
                            <p class="text-sm font-semibold text-ink">{{ __('site.tours.show.meeting_point_title') }}</p>
                            <p class="mt-1 text-sm text-text-2">{{ $tour['meeting_point'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ITINERARIO ============ --}}
    @if(!empty($tour['itinerary']))
        <section class="bg-ground">
            <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
                <x-ui.section-title as="h2">{{ __('site.tours.show.itinerary_title') }}</x-ui.section-title>

                <div>
                    @foreach($tour['itinerary'] as $day)
                        <x-ui.faq-item :question="$day['title']">{{ $day['description'] }}</x-ui.faq-item>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ INCLUYE / NO INCLUYE ============ --}}
    @if(!empty($tour['inclusions']) || !empty($tour['exclusions']))
        <section class="bg-surface">
            <div class="mx-auto grid max-w-4xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:px-8">
                @if(!empty($tour['inclusions']))
                    <div>
                        <h2 class="font-display text-xl font-semibold text-ink">{{ __('site.tours.show.inclusions_title') }}</h2>
                        <ul class="mt-4 flex flex-col gap-3">
                            @foreach($tour['inclusions'] as $item)
                                <li class="flex items-start gap-2 text-sm text-text-2">{!! $iconCheck !!}<span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($tour['exclusions']))
                    <div>
                        <h2 class="font-display text-xl font-semibold text-ink">{{ __('site.tours.show.exclusions_title') }}</h2>
                        <ul class="mt-4 flex flex-col gap-3">
                            @foreach($tour['exclusions'] as $item)
                                <li class="flex items-start gap-2 text-sm text-text-2">{!! $iconX !!}<span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ============ CTA: MÁS TOURS EN ESTE DESTINO ============ --}}
    @if($tour['destination'])
        <section class="bg-ground">
            <div class="mx-auto flex max-w-4xl flex-col items-start gap-4 px-4 py-12 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p class="font-display text-xl font-semibold text-ink">
                    {{ __('site.tours.show.cta_banner_title', ['destination' => $tour['destination']['name']]) }}
                </p>
                <x-ui.button variant="secondary" href="{{ route('tours.index', ['destino' => $tour['destination']['slug']]) }}" class="w-full justify-center sm:w-auto">
                    {{ __('site.tours.show.cta_banner_button') }}
                </x-ui.button>
            </div>
        </section>
    @endif

</x-layout>
