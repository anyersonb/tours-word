@php
    use App\Models\TeamMember;
    use App\Support\PlaceholderImage;

    /**
     * D2: la tabla arranca vacía a propósito (las 4 personas del mockup son
     * caras generadas por IA con nombres inventados) y la sección "Nuestro
     * equipo" no se renderiza sin registros publicados — ver el @if de la
     * sección 5 de abajo. No se cablea una rejilla de 4 columnas asumiendo
     * 4 elementos: ver el condicional carrusel/tarjeta suelta.
     */
    $teamMembers = TeamMember::query()->published()->ordered()->get();

    // Íconos: mismo lenguaje visual (stroke-width 2, viewBox 24x24) que
    // home.blade.php/contact.blade.php, para no introducir un tercer estilo.
    $iconShieldCheck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>';
    $iconLeaf = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M5 21c8 0 14-6 14-16-10 0-16 6-16 14 0 .7.05 1.35.14 2Z"/><path d="M5 21c3-4 6-7 12-11"/></svg>';
    $iconAward = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="m8.5 13.5-1.8 6.5 5.3-3 5.3 3-1.8-6.5"/></svg>';
    $iconHeart = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M12 20s-7-4.35-9.5-8.5C.7 8 2 4.5 5.5 4a4.8 4.8 0 0 1 6.5 2 4.8 4.8 0 0 1 6.5-2C22 4.5 23.3 8 21.5 11.5 19 15.65 12 20 12 20Z"/></svg>';

    $iconInstagram = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6" fill="currentColor" stroke="none"/></svg>';
    $iconFacebook = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M14 21v-7h3l1-4h-4V7.5A1.5 1.5 0 0 1 15.5 6H18V3h-3a4.5 4.5 0 0 0-4.5 4.5V10H8v4h2.5v7Z"/></svg>';
    $iconWhatsapp = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M12 2a9 9 0 0 0-7.8 13.5L3 22l6.7-1.2A9 9 0 1 0 12 2Z"/><path d="M8.5 9.2c.3 3.5 2.8 6 6.3 6.3"/></svg>';

    $valueIcons = [$iconShieldCheck, $iconLeaf, $iconAward, $iconHeart];

    $valueItems = __('site.nosotros.values.items');
@endphp
<x-layout title="{{ __('site.nav.about') }}" description="{{ __('site.nosotros.meta.description') }}">

    {{-- ============ MIGAS DE PAN + HERO PARTIDO ============ --}}
    {{--
        D1/hero: hero partido (2 columnas, foto confinada a la mitad
        derecha), igual patrón que Contacto, no el de ancho completo de la
        Home. El texto ("Nosotros" + línea verde) cae sobre --surface, no
        sobre la foto: mismo caso ya verificado para Home/Contacto en
        docs/lote-1/00-sistema-diseno.md §2 — no hace falta velo.
    --}}
    <section class="overflow-hidden bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.nosotros.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.nosotros.breadcrumb.current')],
            ]" />
        </div>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 pb-24 pt-8 sm:px-6 sm:pb-20 lg:grid-cols-2 lg:items-center lg:gap-12 lg:px-8 lg:pb-24">
            <div>
                <h1 class="font-display text-3xl font-semibold leading-[1.15] text-ink sm:text-4xl lg:text-5xl">
                    {{ __('site.nosotros.hero.title') }}
                </h1>

                <p class="mt-3 text-lg font-semibold text-brand-text">
                    {{ __('site.nosotros.hero.tagline') }}
                </p>

                <p class="mt-4 max-w-xl text-base text-text-2 sm:text-lg">
                    {{ __('site.nosotros.hero.description') }}
                </p>
            </div>

            <div class="aspect-[4/5] w-full overflow-hidden rounded-3xl bg-surface-2 sm:aspect-[16/9] lg:aspect-[4/5]">
                <img
                    src="{{ PlaceholderImage::svg(1000, 1250, 'Foto de equipo (pendiente)', '1b6949') }}"
                    alt="{{ __('site.nosotros.hero.photo_alt') }}"
                    width="1000" height="1250"
                    class="h-full w-full object-cover"
                >
            </div>
        </div>
    </section>

    {{--
        D1: tarjeta flotante de estadísticas, montada a caballo entre el
        hero y "Nuestro propósito" — el elemento más frágil del mockup al
        colapsar (brief). Reusa x-ui.stats-strip TAL CUAL (misma regla dura
        de cero cifras inventadas que ya cumple en la Home): no se duplica
        acá su lectura de Setting. Hermano con margen negativo, no
        absolute+overflow-visible, para no depender de que ningún padre deje
        de recortar. Si stats-strip no imprime nada (sin Setting sembrado,
        el estado real de la base hoy), este bloque queda vacío y el margen
        negativo solo acerca un poco las dos secciones — nunca una caja
        vacía con borde/sombra.
    --}}
    <div class="relative z-10 -mt-20 px-4 sm:-mt-16 sm:px-6 lg:-mt-14 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <x-ui.stats-strip />
        </div>
    </div>

    {{-- ============ NUESTRO PROPÓSITO ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 pb-12 pt-16 sm:px-6 sm:pb-16 sm:pt-14 lg:px-8 lg:pb-20 lg:pt-16">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div>
                    <x-ui.section-title as="h2">{{ __('site.nosotros.purpose.title') }}</x-ui.section-title>

                    <p class="text-base text-text-2 sm:text-lg">
                        {{ __('site.nosotros.purpose.paragraph_1') }}
                    </p>
                    <p class="mt-4 text-base text-text-2 sm:text-lg">
                        {{ __('site.nosotros.purpose.paragraph_2') }}
                    </p>

                    <p class="mt-6 font-script text-3xl text-brand-text sm:text-4xl">
                        {{ __('site.nosotros.purpose.signature') }} &#9825;
                    </p>
                </div>

                <div class="aspect-[4/3] w-full overflow-hidden rounded-3xl bg-surface-2">
                    <img
                        src="{{ PlaceholderImage::svg(900, 675, 'Foto de propósito (pendiente)', '2c6fa8') }}"
                        alt="{{ __('site.nosotros.purpose.photo_alt') }}"
                        width="900" height="675" loading="lazy"
                        class="h-full w-full object-cover"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- ============ NUESTROS VALORES ============ --}}
    {{-- D3: sección sobre --ground (fondo pálido), no --surface; medido
         antes de fijar el color de texto (ver docs). --}}
    <section class="bg-ground">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <x-ui.section-title as="h2">{{ __('site.nosotros.values.title') }}</x-ui.section-title>

            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($valueItems as $i => $item)
                    <x-ui.value-item
                        :icon="$valueIcons[$i] ?? $valueIcons[0]"
                        :title="$item['title']"
                        :description="$item['description']"
                    />
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ NUESTRO EQUIPO ============ --}}
    {{--
        D2: sin registros publicados, la sección entera no existe en el DOM
        (ni título ni descripción) — nunca un encabezado huérfano sobre una
        rejilla vacía. Con 1 persona: tarjeta suelta, igual patrón que
        "Tours destacados" de la Home. Con 2+: carrusel real
        (x-ui.carousel-shell), sin asumir 4 como el mockup.
    --}}
    @if($teamMembers->isNotEmpty())
        <section class="bg-surface">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <x-ui.section-title as="h2">{{ __('site.nosotros.team.title') }}</x-ui.section-title>
                <p class="-mt-4 mb-8 max-w-2xl text-sm text-text-2 sm:text-base">
                    {{ __('site.nosotros.team.description') }}
                </p>

                @if($teamMembers->count() >= 2)
                    <x-ui.carousel-shell label="{{ __('site.nosotros.team.title') }}">
                        @foreach($teamMembers as $member)
                            <li class="w-[240px] shrink-0 snap-start sm:w-[260px]">
                                <x-ui.team-member-card
                                    :photo="$member->photoUrl() ?? PlaceholderImage::svg(320, 240, $member->name, '3d4a42')"
                                    :name="$member->name"
                                    :role="$member->role"
                                    :bio="$member->description"
                                    :social="array_values(array_filter([
                                        $member->instagram_url ? ['label' => __('site.contacto.info.social_instagram'), 'href' => $member->instagram_url, 'icon' => $iconInstagram] : null,
                                        $member->facebook_url ? ['label' => __('site.contacto.info.social_facebook'), 'href' => $member->facebook_url, 'icon' => $iconFacebook] : null,
                                        $member->whatsapp_url ? ['label' => __('site.contacto.info.social_whatsapp'), 'href' => $member->whatsapp_url, 'icon' => $iconWhatsapp] : null,
                                    ]))"
                                />
                            </li>
                        @endforeach
                    </x-ui.carousel-shell>
                @else
                    <div class="max-w-xs">
                        <x-ui.team-member-card
                            :photo="$teamMembers->first()->photoUrl() ?? PlaceholderImage::svg(320, 240, $teamMembers->first()->name, '3d4a42')"
                            :name="$teamMembers->first()->name"
                            :role="$teamMembers->first()->role"
                            :bio="$teamMembers->first()->description"
                            :social="array_values(array_filter([
                                $teamMembers->first()->instagram_url ? ['label' => __('site.contacto.info.social_instagram'), 'href' => $teamMembers->first()->instagram_url, 'icon' => $iconInstagram] : null,
                                $teamMembers->first()->facebook_url ? ['label' => __('site.contacto.info.social_facebook'), 'href' => $teamMembers->first()->facebook_url, 'icon' => $iconFacebook] : null,
                                $teamMembers->first()->whatsapp_url ? ['label' => __('site.contacto.info.social_whatsapp'), 'href' => $teamMembers->first()->whatsapp_url, 'icon' => $iconWhatsapp] : null,
                            ]))"
                        />
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ============ BANDA DE CTA ============ --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="grid overflow-hidden rounded-3xl border border-line bg-ground lg:grid-cols-2">
                <div class="aspect-[16/9] lg:aspect-auto">
                    <img
                        src="{{ PlaceholderImage::svg(700, 600, 'Foto de CTA (pendiente)', '135338') }}"
                        alt="{{ __('site.nosotros.cta.photo_alt') }}"
                        width="700" height="600" loading="lazy"
                        class="h-full w-full object-cover"
                    >
                </div>
                <div class="relative flex flex-col justify-center gap-4 p-8 sm:p-10">
                    <h2 class="font-display text-2xl font-semibold text-ink sm:text-3xl">
                        {{ __('site.nosotros.cta.title') }}
                    </h2>
                    <p class="text-text-2">{{ __('site.nosotros.cta.description') }}</p>

                    <div class="mt-2">
                        <x-ui.button href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}">
                            {{ __('site.nosotros.cta.button') }} &rarr;
                        </x-ui.button>
                    </div>

                    {{-- Avioncito + línea de puntos: decorativo, aria-hidden. --}}
                    <svg viewBox="0 0 160 90" class="pointer-events-none absolute bottom-4 right-4 hidden h-16 w-28 text-action/50 sm:block" aria-hidden="true">
                        <path d="M8 78c50 4 60-42 148-58" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="1 8" stroke-linecap="round"/>
                        <path d="m142 12-14 4 6 6 4 10 8-20-4 0Z" fill="currentColor"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>

</x-layout>
