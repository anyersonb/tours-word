@props([
    'images' => [], // [['src' => 'url', 'alt' => '...'], ...] — SIEMPRE al menos 1 (responsabilidad del llamador)
    'label' => 'Galería de fotos',
])
{{--
    Componente único de galería de ficha (lote 3), reutilizado por las 3
    fichas (tour/destino/experiencia): imagen principal + tira de
    miniaturas cuando hay más de una foto. Mismo patrón de accesibilidad que
    x-ui.carousel-shell (role="region" + aria-label) y que x-ui.money
    (x-cloak + <noscript> como respaldo sin JS).
--}}
<div x-data="{ active: 0 }" class="flex flex-col gap-3" role="region" aria-label="{{ $label }}">
    <div class="aspect-[16/10] w-full overflow-hidden rounded-3xl bg-surface-2 sm:aspect-[16/9]">
        @foreach($images as $i => $image)
            <img
                x-show="active === {{ $i }}"
                x-cloak
                src="{{ $image['src'] }}"
                alt="{{ $image['alt'] }}"
                loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                width="1200" height="750"
                class="h-full w-full object-cover"
            >
        @endforeach

        <noscript>
            <img
                src="{{ $images[0]['src'] ?? '' }}"
                alt="{{ $images[0]['alt'] ?? '' }}"
                width="1200" height="750"
                class="h-full w-full object-cover"
            >
        </noscript>
    </div>

    @if(count($images) > 1)
        <div
            class="flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            role="group"
            aria-label="{{ __('site.ui.gallery.thumbnails_group', ['title' => $label]) }}"
        >
            @foreach($images as $i => $image)
                <button
                    type="button"
                    @click="active = {{ $i }}"
                    :aria-current="active === {{ $i }} ? 'true' : 'false'"
                    :class="active === {{ $i }} ? 'border-action' : 'border-transparent'"
                    aria-label="{{ __('site.ui.gallery.show_photo', ['position' => $i + 1, 'total' => count($images)]) }}"
                    class="h-16 w-20 shrink-0 overflow-hidden rounded-xl border-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                >
                    <img src="{{ $image['src'] }}" alt="" loading="lazy" width="120" height="80" class="h-full w-full object-cover">
                </button>
            @endforeach
        </div>
    @endif
</div>
