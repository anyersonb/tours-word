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
    // Pase cinematográfico (pasada B, catálogo de tours). false conserva
    // EXACTO el comportamiento anterior (banda --scrim-band, alto de
    // contenido) para destinos/experiencias, que no entran en este pase.
    // true (solo tours/index) sube la cabecera a un bloque de foto a
    // sangre más alto, con --scrim-hero (el mismo scrim del hero de Home,
    // ya medido contra ESTA MISMA foto — hero-cordillera-rio.jpg reutiliza
    // una de las 3 fotos del slider, ver el comentario de --scrim-hero en
    // tokens.css) y parallax sutil con la infraestructura existente.
    'cinematic' => false,
    // Velocidad del data-parallax del bloque cinematic (2026-09-18). Prop
    // aditiva: el default '0.1' es EXACTAMENTE el valor que estaba
    // cableado antes, así que un caller que no la pasa (hoy, ninguno: solo
    // tours/index usa cinematic=true) no cambia de comportamiento. Solo
    // tours/index.blade.php la sube a '0.2' para igualar la amplitud del
    // resto de heroes del sitio — ver home.blade.php y tours/show.blade.php.
    'parallaxSpeed' => '0.1',
])
{{--
    Cabecera única de las pantallas interiores (índices y fichas).

    Antes cada una repetía su propio bloque "migas + h1 + párrafo" con sus
    propios paddings (pt-6 / pb-8 / pt-4) sobre blanco, y las tres quedaban
    ligeramente distintas. Acá vive una sola vez, con tres variantes:

      - con foto: banda oscura con el scrim de banda (--scrim-band), texto
        claro. El scrim es lo que hace legible el texto sobre la foto, así
        que sale del token y no de una opacidad puesta a mano.
      - con foto + cinematic: mismo principio, pero foto a sangre más alta,
        --scrim-hero (más fuerte) y parallax.
      - sin foto: banda cálida sobre --sand con el motivo geométrico de
        marca. Nunca una foto de relleno para "no dejarlo vacío".
--}}
@if($image && $cinematic)
    <section class="relative isolate flex min-h-[58svh] flex-col justify-end overflow-hidden bg-ink-surface sm:min-h-[64svh] lg:min-h-[70svh]">
        <div class="photo scrim-hero absolute inset-0" aria-hidden="true">
            <div class="parallax-frame" data-parallax="{{ $parallaxSpeed }}">
                <x-ui.picture
                    :src="$image"
                    :alt="$imageAlt"
                    sizes="100vw"
                    loading="eager"
                    fetchpriority="high"
                    decoding="sync"
                    :position="$position"
                    imgClass="h-full w-full object-cover"
                />
            </div>
        </div>

        <div class="shell relative z-10 pb-12 pt-28 sm:pb-16 sm:pt-32 lg:pb-20">
            @if(filled($breadcrumbs))
                <x-ui.breadcrumbs :items="$breadcrumbs" tone="dark" class="mb-6" />
            @endif

            @if($eyebrow)
                <x-ui.eyebrow variant="on-dark" class="mb-4">{{ $eyebrow }}</x-ui.eyebrow>
            @endif

            <h1 class="max-w-3xl font-display text-hero font-semibold text-white">{{ $title }}</h1>

            @if($lead)
                <p class="mt-4 max-w-2xl text-lead text-on-dark-2">{{ $lead }}</p>
            @endif

            @isset($actions)
                <div class="mt-7 flex flex-wrap items-center gap-3">{{ $actions }}</div>
            @endisset
        </div>
    </section>
@elseif($image)
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
