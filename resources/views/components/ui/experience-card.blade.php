@props([
    'image',
    'imageAlt' => '',
    'title',
    'description' => null,
    'href' => '#',
    'icon' => null,
])
<a href="{{ $href }}" class="group block focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
    <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-surface-2">
        <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy" width="320" height="240" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
        @if($icon)
            <span class="absolute left-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-surface text-action shadow-sm" aria-hidden="true">
                {!! $icon !!}
            </span>
        @endif
    </div>
    <h3 class="mt-3 font-display text-base font-semibold text-ink">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 text-sm text-text-2">{{ $description }}</p>
    @endif
</a>
