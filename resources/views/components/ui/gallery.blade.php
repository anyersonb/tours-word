@props([
    'images' => [], // [['src' => 'url', 'alt' => '...'], ...] — SIEMPRE al menos 1 (responsabilidad del llamador)
    'label' => 'Galería de fotos',
])
{{--
    Componente único de galería de ficha (lote 3), reutilizado por las 3
    fichas (tour/destino/experiencia): imagen principal + tira de miniaturas
    cuando hay más de una foto. Mismo patrón de accesibilidad que
    x-ui.carousel-shell (role="region" + aria-label) y que x-ui.money
    (x-cloak + <noscript> como respaldo sin JS).

    Pase visual 2026-09-14: las fotos pasan por x-ui.picture (WebP reducido
    con el JPG de respaldo). La miniatura declara un "sizes" chico de verdad
    (80px): antes cada miniatura de 80x64 se bajaba el original de 1400px.
--}}
<div x-data="{ active: 0 }" class="flex flex-col gap-3" role="region" aria-label="{{ $label }}">
    <div class="photo aspect-[16/10] w-full rounded-panel shadow-e2 sm:aspect-[16/9]">
        @foreach($images as $i => $image)
            <div x-show="active === {{ $i }}" x-cloak class="h-full w-full">
                <x-ui.picture
                    :src="$image['src']"
                    :alt="$image['alt']"
                    :loading="$i === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="$i === 0 ? 'high' : null"
                    sizes="(min-width: 1024px) 46rem, 96vw"
                />
            </div>
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
            class="flex gap-2.5 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            role="group"
            aria-label="{{ __('site.ui.gallery.thumbnails_group', ['title' => $label]) }}"
        >
            @foreach($images as $i => $image)
                <button
                    type="button"
                    @click="active = {{ $i }}"
                    :aria-current="active === {{ $i }} ? 'true' : 'false'"
                    :class="active === {{ $i }} ? 'border-action opacity-100' : 'border-transparent opacity-70 hover:opacity-100'"
                    aria-label="{{ __('site.ui.gallery.show_photo', ['position' => $i + 1, 'total' => count($images)]) }}"
                    class="photo h-16 w-20 shrink-0 rounded-md border-2 transition-opacity focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                >
                    <x-ui.picture :src="$image['src']" alt="" sizes="80px" />
                </button>
            @endforeach
        </div>
    @endif
</div>
