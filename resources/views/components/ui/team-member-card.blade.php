@props([
    'photo',
    'name',
    'role' => null,
    'bio' => null,
    'social' => [], // [['label' => 'Instagram', 'href' => '#', 'icon' => '<svg>...']]
])
<article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">
    <div class="aspect-[4/3] overflow-hidden bg-surface-2">
        <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" width="320" height="240" class="h-full w-full object-cover">
    </div>
    <div class="p-5">
        <h3 class="font-display text-base font-semibold text-ink">{{ $name }}</h3>
        @if($role)
            <p class="text-sm text-brand-text">{{ $role }}</p>
        @endif
        @if($bio)
            <p class="mt-2 text-sm text-text-2">{{ $bio }}</p>
        @endif

        @if(count($social))
            <div class="mt-3 flex items-center gap-2">
                @foreach($social as $link)
                    <a
                        href="{{ $link['href'] }}"
                        aria-label="{{ $link['label'] }}"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-action hover:bg-action hover:text-on-action"
                    >
                        {!! $link['icon'] ?? '' !!}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</article>
