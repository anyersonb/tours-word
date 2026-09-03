@props([
    'image',
    'imageAlt' => '',
    'name',
    'tagline' => null,
    'href' => '#',
])
<a href="{{ $href }}" class="group relative block aspect-[4/5] overflow-hidden rounded-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
    <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy" width="320" height="400" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
    <span class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent" aria-hidden="true"></span>
    <span class="absolute inset-x-0 bottom-0 p-4 text-white">
        <span class="block font-display text-lg font-semibold">{{ $name }}</span>
        @if($tagline)
            <span class="block text-sm text-white/85">{{ $tagline }}</span>
        @endif
    </span>
</a>
