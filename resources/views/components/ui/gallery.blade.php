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

    Pase cinematográfico (pasada B, 2026-09-18): botón "ver a pantalla
    completa" que abre un lightbox modal (Alpine.data('gallery'), en
    app.js). Aditivo puro — el bloque de foto principal + miniaturas de
    arriba no cambia en absoluto, así que destinos/experiencias (que
    también consumen este componente y quedan fuera de este pase) ganan el
    lightbox gratis sin ningún cambio visual en lo que ya tenían.

    Accesibilidad del lightbox: Esc cierra, ← → navegan, foco atrapado
    dentro mientras está abierto (trampa de Tab escrita a mano — el
    proyecto no trae @alpinejs/focus y la regla del lote es cero
    dependencias npm nuevas), y el foco vuelve al botón que lo abrió al
    cerrar. "active" es EL MISMO estado que ya usan las miniaturas: abrir el
    lightbox en la foto que estaba activa y navegar dentro del lightbox deja
    la tira de miniaturas sincronizada al cerrar.
--}}
<div x-data="gallery({{ count($images) }})" class="flex flex-col gap-3" role="region" aria-label="{{ $label }}">
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

        <button
            type="button"
            @click="show(active)"
            aria-label="{{ __('site.ui.gallery.expand') }}"
            class="absolute bottom-3 right-3 z-[2] flex h-10 w-10 items-center justify-center rounded-full bg-ink-surface/70 text-white backdrop-blur-sm transition-colors hover:bg-ink-surface focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m0 8v3a2 2 0 0 0 2 2h3m8 0h3a2 2 0 0 0 2-2v-3m0-8V5a2 2 0 0 0-2-2h-3"/></svg>
        </button>

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

    {{-- Lightbox modal. x-cloak: sin JS nunca se ve (y el botón que lo abre
         tampoco hace nada sin JS, pero la foto principal ya es visible por
         defecto arriba, así que la página nunca queda muda). --}}
    <div
        x-show="lightboxOpen"
        x-cloak
        x-ref="lightboxDialog"
        x-effect="document.documentElement.classList.toggle('overflow-hidden', lightboxOpen)"
        @keydown="onKeydown($event)"
        @keydown.escape.window="lightboxOpen && hide()"
        @click.self="hide()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink-surface/95 p-4 backdrop-blur-sm sm:p-8"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $label }}"
        tabindex="-1"
    >
        <button
            type="button"
            @click="hide()"
            aria-label="{{ __('site.ui.gallery.close') }}"
            class="absolute right-4 top-4 z-[1] flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/25 backdrop-blur-sm hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>

        @if(count($images) > 1)
            <button
                type="button"
                @click="prev()"
                aria-label="{{ __('site.ui.gallery.previous') }}"
                class="absolute left-2 top-1/2 z-[1] flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/25 backdrop-blur-sm hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:left-4"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button
                type="button"
                @click="next()"
                aria-label="{{ __('site.ui.gallery.next') }}"
                class="absolute right-2 top-1/2 z-[1] flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/25 backdrop-blur-sm hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:right-4"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        @endif

        <div class="max-h-[85vh] max-w-5xl" @click.stop>
            @foreach($images as $i => $image)
                <div x-show="active === {{ $i }}" x-cloak>
                    <img
                        src="{{ $image['src'] }}"
                        alt="{{ $image['alt'] }}"
                        class="max-h-[85vh] w-auto max-w-full rounded-lg object-contain"
                        loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                        decoding="async"
                    >
                </div>
            @endforeach
        </div>

        @if(count($images) > 1)
            <p class="absolute bottom-4 left-1/2 z-[1] -translate-x-1/2 text-sm text-on-dark-2" aria-hidden="true" x-text="(active + 1) + ' / ' + count"></p>
        @endif
    </div>
</div>
