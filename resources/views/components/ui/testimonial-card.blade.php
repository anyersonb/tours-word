@props([
    'quote',
    'name',
    'origin' => null,
    'rating' => 5,
    'avatar' => null,
    'sample' => false,
])
{{--
    Cero reseñas inventadas (regla dura del proyecto). Este componente NO
    decide si hay o no reseñas reales: eso lo resuelve la página que lo usa
    (si no hay fuente verificable, la sección entera no se renderiza). Acá
    solo se exige marcar visualmente cualquier contenido de muestra.
--}}
<figure class="relative flex flex-col gap-4 rounded-2xl border border-line bg-surface p-6 shadow-sm">
    @if($sample)
        <span class="absolute right-4 top-4 rounded-full bg-surface-2 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-text-muted">Muestra</span>
    @endif

    {{-- Ícono gráfico (no texto): --amber pasa el mínimo no-textual de 3:1
         (3.14:1 medido), aunque no sirva como color de texto. --}}
    <div class="flex items-center gap-0.5 text-amber" aria-label="{{ $rating }} de 5 estrellas">
        @for($i = 1; $i <= 5; $i++)
            <svg class="h-4 w-4 {{ $i <= $rating ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                <path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z" />
            </svg>
        @endfor
    </div>

    <blockquote class="text-sm text-text-2">&ldquo;{{ $quote }}&rdquo;</blockquote>

    <figcaption class="flex items-center gap-3">
        @if($avatar)
            <img src="{{ $avatar }}" alt="" loading="lazy" width="40" height="40" class="h-10 w-10 rounded-full object-cover">
        @endif
        <div>
            <p class="text-sm font-semibold text-ink">{{ $name }}</p>
            @if($origin)
                <p class="text-xs text-text-muted">{{ $origin }}</p>
            @endif
        </div>
    </figcaption>
</figure>
