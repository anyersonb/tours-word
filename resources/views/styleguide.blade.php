@php
    if (! function_exists('pv_placeholder')) {
        // Placeholder local (data URI, sin red): las fotos reales del catálogo
        // llegan en el lote de contenido; esto solo evita depender de un
        // servicio externo para ver el layout de las tarjetas.
        function pv_placeholder(int $w, int $h, string $label, string $hex = '2c6fa8'): string
        {
            $hex = ltrim($hex, '#');
            $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
                <rect width="100%" height="100%" fill="#{$hex}" />
                <text x="50%" y="50%" fill="#ffffff" font-family="sans-serif" font-size="14" text-anchor="middle" dominant-baseline="middle">{$label}</text>
            </svg>
            SVG;

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        }
    }
@endphp
<x-layout title="Guía de estilos" :noindex="true">
    <div class="mx-auto max-w-7xl space-y-16 px-4 py-12 sm:px-6 lg:px-8">

        <header>
            <p class="text-sm font-semibold uppercase tracking-wide text-action">Lote 1 · Etapa A</p>
            <h1 class="font-display text-3xl font-bold text-ink sm:text-4xl">Guía de estilos — Pacha Viva</h1>
            <p class="mt-2 max-w-2xl text-text-2">
                Inventario vivo de los componentes del sistema de diseño (A7). Página con
                <code class="rounded bg-surface-2 px-1.5 py-0.5 text-sm">noindex</code>, no forma parte del sitio público.
                Detalle de tokens, contraste y decisiones en <code class="rounded bg-surface-2 px-1.5 py-0.5 text-sm">docs/lote-1/00-sistema-diseno.md</code>.
            </p>
        </header>

        {{-- ================= CADENA DE TOKENS ================= --}}
        <section aria-labelledby="tokens-h">
            <x-ui.section-title id="tokens-h" as="h2">Cadena de tokens (A1)</x-ui.section-title>
            <p class="mb-4 max-w-2xl text-sm text-text-2">
                Estos cuadros leen <code>bg-brand-*</code> / <code>text-action</code> generados por Tailwind 4 a partir
                de <code>tokens.css</code>. Prueba de vida: abre DevTools, cambia
                <code>--brand-h</code> en <code>:root</code> (por ejemplo a <code>12</code>) y estos colores deben
                recolorearse sin recompilar nada — si no cambian, la cadena está rota.
            </p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5 lg:grid-cols-10">
                @foreach([50,100,200,300,400,500,600,700,800,900] as $stop)
                    <div class="overflow-hidden rounded-lg border border-line">
                        <div class="h-14 bg-brand-{{ $stop }}"></div>
                        <p class="bg-surface p-1.5 text-center text-[11px] text-text-2">brand-{{ $stop }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap items-end gap-6">
                <p class="font-display text-3xl font-semibold text-ink">Fraunces — font-display</p>
                <p class="font-sans text-lg text-ink">Figtree — font-sans (texto de interfaz)</p>
                <p class="font-script text-2xl text-brand-text">Caveat — font-script</p>
            </div>
        </section>

        {{-- ================= BOTONES ================= --}}
        <section aria-labelledby="buttons-h">
            <x-ui.section-title id="buttons-h" as="h2">Botones</x-ui.section-title>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary">Primario</x-ui.button>
                <x-ui.button variant="secondary">Secundario</x-ui.button>
                <x-ui.button variant="ghost">Fantasma</x-ui.button>
                <x-ui.button variant="link">Ver todos los tours →</x-ui.button>
                <x-ui.button variant="primary" disabled>Deshabilitado</x-ui.button>
                <x-ui.button variant="primary" :icon="'<svg class=\'h-4 w-4\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M5 12h14M13 6l6 6-6 6\'/></svg>'">
                    Con ícono
                </x-ui.button>
            </div>
            <p class="mt-3 text-sm text-text-muted">Foco visible: navega con Tab para ver el anillo de <code>--action</code> en cada botón.</p>
        </section>

        {{-- ================= BADGES / EYEBROWS ================= --}}
        <section aria-labelledby="badges-h">
            <x-ui.section-title id="badges-h" as="h2">Badges y eyebrows</x-ui.section-title>
            <p class="mb-3 max-w-2xl text-sm text-text-2">4 acentos admitidos (ver tokens.css): verde de marca, ámbar, naranja y azul. Naranja y azul están fuera del hue 155 a propósito.</p>
            <div class="flex flex-wrap gap-3">
                <x-ui.badge variant="orange">Más vendido</x-ui.badge>
                <x-ui.badge variant="blue">Recomendado</x-ui.badge>
                <x-ui.badge variant="green">Naturaleza</x-ui.badge>
                <x-ui.badge variant="amber">Certificado</x-ui.badge>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-ui.eyebrow variant="brand">Estamos para ayudarte</x-ui.eyebrow>
                <x-ui.mincetur-badge />
                <span class="self-center text-xs text-text-muted">(el badge MINCETUR de arriba solo aparece si hay <code>rnavt_number</code> en Setting — hoy no lo hay, por eso no se ve nada a la derecha de esta nota)</span>
            </div>
        </section>

        {{-- ================= TARJETAS ================= --}}
        <section aria-labelledby="cards-h" class="space-y-10">
            <x-ui.section-title id="cards-h" as="h2">Tarjetas</x-ui.section-title>

            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-text-muted">Tour</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <x-ui.tour-card
                        :image="pv_placeholder(480,360,'Camino Inca','1b6949')"
                        badge="Más vendido" badge-variant="orange"
                        title="[MUESTRA] Camino Inca 4 días"
                        summary="Tour de muestra: trekking clásico hasta Machu Picchu."
                        duration="4 días / 3 noches" category="Trekking"
                        :pen-cents="350000" :usd-cents="9500"
                    />
                    <x-ui.tour-card
                        :image="pv_placeholder(480,360,'Gastronomía AQP','2c6fa8')"
                        badge="Recomendado" badge-variant="blue"
                        title="[MUESTRA] Tour gastronómico en Arequipa"
                        summary="Tour de muestra: ruta de picanterías tradicionales."
                        duration="4 horas" category="Gastronomía"
                        :pen-cents="12000" :usd-cents="3200"
                    />
                    <x-ui.tour-card
                        :image="pv_placeholder(480,360,'Sin badge','6c7972')"
                        title="[MUESTRA] Tour sin insignia"
                        summary="Estado sin badge, para verificar que el layout no depende de él."
                        duration="1 día" category="Cultura"
                        :pen-cents="8000" :usd-cents="2150"
                    />
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-text-muted">Destino</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <x-ui.destination-card :image="pv_placeholder(320,400,'Cusco','135338')" name="Cusco" tagline="La capital histórica del imperio inca" />
                    <x-ui.destination-card :image="pv_placeholder(320,400,'Arequipa','0e3f2a')" name="Arequipa" tagline="Conocida como la Ciudad Blanca" />
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-text-muted">Experiencia</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <x-ui.experience-card :image="pv_placeholder(320,240,'Trekking','1b6949')" title="Caminatas y Trekking" description="Rutas en los Andes." />
                    <x-ui.experience-card :image="pv_placeholder(320,240,'Cultura','93590c')" title="Cultura y Tradición" description="Historia y costumbres locales." />
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-text-muted">Testimonio</h3>
                <p class="mb-3 max-w-2xl text-sm text-text-2">
                    Cero reseñas inventadas: el contenido de abajo está marcado explícitamente como
                    <strong>Muestra</strong>. En una página real, sin fuente verificable de reseñas, esta
                    sección completa no se renderiza.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.testimonial-card
                        :sample="true"
                        quote="Contenido de muestra para probar el layout de la tarjeta de testimonio."
                        name="Nombre de muestra" origin="País de muestra" :rating="5"
                    />
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-text-muted">Ficha de equipo</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <x-ui.team-member-card
                        :photo="pv_placeholder(320,240,'Guía','2c6fa8')"
                        name="Nombre de muestra" role="Guía de montaña (muestra)"
                        bio="Contenido de muestra, pendiente de la clienta."
                        :social="[['label' => 'Instagram', 'href' => '#', 'icon' => '']]"
                    />
                </div>
            </div>
        </section>

        {{-- ================= TÍTULO DE SECCIÓN ================= --}}
        <section aria-labelledby="title-h">
            <x-ui.section-title id="title-h" as="h2">Título de sección con subrayado</x-ui.section-title>
            <x-ui.section-title as="h3">Ejemplo: ¿Por qué elegir viajar con nosotros?</x-ui.section-title>
        </section>

        {{-- ================= FRANJA DE ESTADÍSTICAS ================= --}}
        <section aria-labelledby="stats-h">
            <x-ui.section-title id="stats-h" as="h2">Franja de estadísticas</x-ui.section-title>
            <p class="mb-3 max-w-2xl text-sm text-text-2">
                Sin cifras inventadas: hoy no hay ningún <code>Setting</code> de estadísticas cargado, así
                que <code>&lt;x-ui.stats-strip /&gt;</code> no imprime nada aquí abajo (compárese con la
                fila de muestra forzada, marcada como tal).
            </p>
            <x-ui.stats-strip class="mb-4" />
            <div class="grid grid-cols-2 gap-6 rounded-2xl border border-dashed border-line bg-surface p-6 sm:grid-cols-4">
                <x-ui.stat value="—" label="(muestra forzada, no viene de Setting)" />
            </div>
        </section>

        {{-- ================= FORMULARIO ================= --}}
        <section aria-labelledby="form-h">
            <x-ui.section-title id="form-h" as="h2">Controles de formulario</x-ui.section-title>
            <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" onsubmit="return false;">
                <x-ui.form.input name="nombre" label="Nombre completo" placeholder="Ingresa tu nombre" required />
                <x-ui.form.input name="correo_error" label="Correo electrónico" type="email" placeholder="Ingresa tu correo" required error="Ingresa un correo válido." />
                <x-ui.form.select name="asunto" label="Asunto" placeholder="Selecciona un asunto" :options="['reserva' => 'Reserva', 'consulta' => 'Consulta general']" required />
                <x-ui.form.checkbox name="privacidad" label="Acepto la política de privacidad." />
                <x-ui.form.textarea name="mensaje" label="Mensaje" placeholder="Cuéntanos cómo podemos ayudarte..." class="sm:col-span-2" required />
            </form>
        </section>

        {{-- ================= ACORDEÓN FAQ ================= --}}
        <section aria-labelledby="faq-h">
            <x-ui.section-title id="faq-h" as="h2">Acordeón de preguntas frecuentes</x-ui.section-title>
            <div class="max-w-2xl rounded-2xl border border-line bg-surface px-5">
                <x-ui.faq-item question="¿Cómo puedo reservar un tour?">
                    Contenido de muestra de la respuesta.
                </x-ui.faq-item>
                <x-ui.faq-item question="¿Cuáles son los métodos de pago?">
                    Contenido de muestra de la respuesta.
                </x-ui.faq-item>
            </div>
        </section>

        {{-- ================= CARRUSEL ================= --}}
        <section aria-labelledby="carousel-h">
            <x-ui.section-title id="carousel-h" as="h2">Carcasa de carrusel</x-ui.section-title>
            <x-ui.carousel-shell label="Ejemplo de carrusel">
                @foreach(['1b6949','2c6fa8','c2410c','93590c'] as $hex)
                    <li class="w-64 shrink-0 snap-start">
                        <img src="{{ pv_placeholder(256,180,'Slide','#'.$hex) }}" alt="" class="h-full w-full rounded-xl object-cover">
                    </li>
                @endforeach
            </x-ui.carousel-shell>
        </section>

    </div>
</x-layout>
