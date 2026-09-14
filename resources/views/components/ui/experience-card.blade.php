@props([
    'image',
    'imageAlt' => '',
    'title',
    'description' => null,
    'href' => '#',
    'icon' => null,
    'sizes' => '(min-width: 1024px) 20rem, (min-width: 640px) 45vw, 90vw',
    // Mismo contrato que x-ui.tour-card: el nivel lo decide la pantalla que
    // coloca la tarjeta (2 en un índice, 3 dentro de una sección con su H2).
    'headingLevel' => 3,
])
@php
    $hl = max(2, min(4, (int) $headingLevel));
@endphp
{{--
    Tarjeta de experiencia: foto + pastilla de icono + texto sobre superficie
    blanca. Antes el texto colgaba directamente del fondo de la sección, sin
    caja: con 3 tarjetas en una rejilla ancha se leía como tres fotos sueltas
    y no como una familia. La caja es lo que las agrupa.
--}}
<a
    href="{{ $href }}"
    {{ $attributes->class(['group flex h-full flex-col overflow-hidden rounded-card border border-line bg-surface shadow-e1 transition-[box-shadow,transform] duration-300 hover:-translate-y-1 hover:shadow-e3']) }}
>
    <span class="photo photo-zoom block aspect-[4/3] w-full">
        <x-ui.picture :src="$image" :alt="$imageAlt" :sizes="$sizes" />
        @if($icon)
            <span class="absolute left-3 top-3 z-[2] flex h-9 w-9 items-center justify-center rounded-full bg-surface text-action shadow-e1" aria-hidden="true">
                {!! $icon !!}
            </span>
        @endif
    </span>

    {{-- Contenedores en <div> y no en <span>: el título es un encabezado de
         verdad y un <span> solo admite contenido de texto. El <a> envolvente
         sí lo admite (modelo de contenido transparente) y se mantiene como
         envoltorio para que el objetivo táctil siga siendo la tarjeta entera.
         El encabezado es solo el título: la flecha se queda fuera de él, es
         decoración con aria-hidden y no debe entrar en el esquema. --}}
    <div class="flex flex-1 flex-col gap-2 p-5">
        <div class="flex items-center gap-2">
            <h{{ $hl }} class="font-display text-h3 font-semibold text-ink">{{ $title }}</h{{ $hl }}>
            <svg class="h-4 w-4 shrink-0 text-action opacity-0 transition-all duration-300 group-hover:translate-x-0.5 group-hover:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
        @if($description)
            <span class="line-clamp-3 text-sm leading-relaxed text-text-2">{{ $description }}</span>
        @endif
    </div>
</a>
