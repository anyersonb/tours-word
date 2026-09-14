@props([
    'breadcrumbs' => [],
    'title',
    'lead' => null,
    'eyebrow' => null,
    // Ruta pública de una foto de cabecera. Sin foto, la cabecera cae a la
    // banda cálida (--sand): no se inventa una foto de relleno.
    'image' => null,
    'imageAlt' => '',
    'position' => 'center 45%',
])
{{--
    Cabecera única de las pantallas interiores (índices y fichas).

    Antes cada una repetía su propio bloque "migas + h1 + párrafo" con sus
    propios paddings (pt-6 / pb-8 / pt-4) sobre blanco, y las tres quedaban
    ligeramente distintas. Acá vive una sola vez, con dos variantes:

      - con foto: banda oscura con el scrim de banda (--scrim-band), texto
        claro. El scrim es lo que hace legible el texto sobre la foto, así
        que sale del token y no de una opacidad puesta a mano.
      - sin foto: banda cálida sobre --sand con el motivo geométrico de
        marca. Nunca una foto de relleno para "no dejarlo vacío".
--}}
@if($image)
    <section class="relative isolate overflow-hidden bg-ink-surface">
        <div class="photo scrim-band absolute inset-0">
            <x-ui.picture
                :src="$image"
                :alt="$imageAlt"
                sizes="100vw"
                loading="eager"
                fetchpriority="high"
                decoding="sync"
                :position="$position"
            />
        </div>

        <div class="shell section-tight relative z-10">
            @if(filled($breadcrumbs))
                <x-ui.breadcrumbs :items="$breadcrumbs" tone="dark" class="mb-6" />
            @endif

            @if($eyebrow)
                <x-ui.eyebrow variant="on-dark" class="mb-4">{{ $eyebrow }}</x-ui.eyebrow>
            @endif

            <h1 class="max-w-3xl font-display text-h1 font-semibold text-white">{{ $title }}</h1>

            @if($lead)
                <p class="mt-4 max-w-2xl text-lead text-on-dark-2">{{ $lead }}</p>
            @endif

            @isset($actions)
                <div class="mt-7 flex flex-wrap items-center gap-3">{{ $actions }}</div>
            @endisset
        </div>
    </section>
@else
    <section class="weave border-b border-sand-line bg-sand">
        <div class="shell section-tight">
            @if(filled($breadcrumbs))
                <x-ui.breadcrumbs :items="$breadcrumbs" class="mb-6" />
            @endif

            @if($eyebrow)
                <x-ui.eyebrow class="mb-4">{{ $eyebrow }}</x-ui.eyebrow>
            @endif

            <h1 class="max-w-3xl font-display text-h1 font-semibold text-ink">{{ $title }}</h1>

            @if($lead)
                <p class="mt-4 max-w-2xl text-lead text-text-2">{{ $lead }}</p>
            @endif

            @isset($actions)
                <div class="mt-7 flex flex-wrap items-center gap-3">{{ $actions }}</div>
            @endisset
        </div>
    </section>
@endif
