@props([
    'label' => 'Carrusel',
])
{{--
    Carcasa de carrusel real (no solo decorativa): scroll-snap horizontal +
    flechas que desplazan un slide y puntos que saltan a un slide. Cada
    <li> del slot es un slide.

    A1 (auditoría CRO 02/09, defecto Medio): esto es una región desplazable
    de tarjetas (tours destacados, equipo), NO un conjunto de paneles de
    contenido intercambiables — por eso NO lleva role="tablist"/"tab". Ese
    patrón estaba a medio implementar (sin flechas de teclado entre pestañas,
    sin roving tabindex, puntos sin aria-label) y un ARIA a medias le promete
    al lector de pantalla un comportamiento que no existe. Se trata como lo
    que es: una región (role="region" + aria-label de más abajo) con botones
    de paginación reales, cada uno con su propio aria-label y aria-current
    para anunciar cuál tarjeta está visible.

    A2 (defecto Bajo, mismo informe): los puntos median 8×8 y 24×8 px, bajo
    el mínimo de 24×24. El botón ahora mide 24×24 (h-6 w-6) y el punto visual
    de siempre queda como un <span> interior sin tocar su tamaño gráfico —
    el área de toque crece, el dibujo no.

    R2 (ronda 2, arreglo 3, 02/09): con pocas tarjetas que ya caben en el
    viewport (maxScroll = scrollWidth - clientWidth = 0), flechas y puntos no
    movían nada — un control roto para el visitante. Ahora `maxScroll` se
    mide con un ResizeObserver sobre el track (se recalcula solo al
    redimensionar, sin listener de window a mano) y toda la fila de
    paginación (puntos + flechas) se oculta con `x-show` cuando no hay nada
    que desplazar, y reaparece sola cuando sí lo hay. Oculto por `x-show`
    (display:none) en vez de `disabled`: ningún botón queda enfocable sin
    función.
--}}
<div
    x-data="{
        active: 0,
        count: 0,
        maxScroll: 0,
        init() {
            this.count = this.$refs.track.children.length;
            this.$refs.track.addEventListener('scroll', () => this.sync());
            this.measure();
            new ResizeObserver(() => this.measure()).observe(this.$refs.track);
        },
        measure() {
            const track = this.$refs.track;
            this.maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
        },
        sync() {
            const track = this.$refs.track;
            const slideWidth = track.children[0]?.offsetWidth || 1;
            this.active = Math.round(track.scrollLeft / slideWidth);
        },
        go(i) {
            const track = this.$refs.track;
            const slide = track.children[i];
            if (slide) track.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' });
        },
        prev() { this.go(Math.max(this.active - 1, 0)); },
        next() { this.go(Math.min(this.active + 1, this.count - 1)); },
    }"
    x-init="init()"
    class="relative"
    role="region"
    aria-label="{{ $label }}"
>
    <ul x-ref="track" class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
        {{ $slot }}
    </ul>

    <div class="mt-4 flex items-center justify-between" x-show="maxScroll > 0" x-cloak>
        <div class="flex gap-1.5" role="group" aria-label="{{ __('site.ui.carousel.pagination_group', ['label' => $label]) }}">
            @php
                // ':position'/':total' se sustituyen en el cliente (Alpine):
                // dependen del slide activo y del conteo real de tarjetas,
                // que no existen en tiempo de render de Blade.
                $goToCardLabel = __('site.ui.carousel.go_to_card');
            @endphp
            <template x-for="i in count" :key="i">
                <button
                    type="button"
                    @click="go(i - 1)"
                    :aria-current="active === i - 1 ? 'true' : 'false'"
                    :aria-label="'{{ $goToCardLabel }}'.replace(':position', i).replace(':total', count)"
                    class="flex h-6 w-6 items-center justify-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                >
                    <span
                        :class="active === i - 1 ? 'bg-action w-6' : 'bg-line w-2'"
                        class="h-2 rounded-full transition-all"
                        aria-hidden="true"
                    ></span>
                </button>
            </template>
        </div>

        <div class="flex gap-2">
            <button type="button" @click="prev()" aria-label="Anterior" class="flex h-9 w-9 items-center justify-center rounded-full border border-line text-ink hover:border-action hover:text-action focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </button>
            <button type="button" @click="next()" aria-label="Siguiente" class="flex h-9 w-9 items-center justify-center rounded-full border border-line text-ink hover:border-action hover:text-action focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </button>
        </div>
    </div>
</div>
