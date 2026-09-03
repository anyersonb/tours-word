@props([
    'image',
    'imageAlt' => '',
    'badge' => null,
    'badgeVariant' => 'green',
    'title',
    'summary' => null,
    'duration' => null,
    'category' => null,
    'penCents' => 0,
    'usdCents' => 0,
    'href' => '#',
])
<article class="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-sm transition-shadow hover:shadow-md">
    <div class="relative aspect-[4/3] w-full overflow-hidden bg-surface-2">
        <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy" width="480" height="360" class="h-full w-full object-cover">
        @if($badge)
            <x-ui.badge :variant="$badgeVariant" class="absolute left-3 top-3">{{ $badge }}</x-ui.badge>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <h3 class="font-display text-lg font-semibold text-ink">{{ $title }}</h3>

        @if($summary)
            <p class="line-clamp-2 text-sm text-text-2">{{ $summary }}</p>
        @endif

        @if($duration || $category)
            <div class="flex flex-wrap gap-3 text-xs text-text-muted">
                @if($duration)
                    <span class="inline-flex items-center gap-1">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" /></svg>
                        {{ $duration }}
                    </span>
                @endif
                @if($category)
                    <span class="inline-flex items-center gap-1">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 21V8l8-5 8 5v13" /><path d="M9 21v-6h6v6" /></svg>
                        {{ $category }}
                    </span>
                @endif
            </div>
        @endif

        <div class="mt-auto flex items-center justify-between pt-2">
            <x-ui.money :pen-cents="$penCents" :usd-cents="$usdCents" prefix="Desde" class="font-display text-lg font-semibold text-ink" />
            <x-ui.button :href="$href" size="sm">Ver tour</x-ui.button>
        </div>
    </div>
</article>
