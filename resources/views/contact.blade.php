@php
    use App\Models\Setting;
    use App\Support\PlaceholderImage;

    /**
     * C2: ninguno de estos datos está cableado. Todos salen de Setting; hoy
     * (sin sembrar nada) todas las variables de abajo son null y cada
     * tarjeta/bloque que depende de ellas se oculta sola.
     */
    $contactPhone = Setting::get('contact_phone');
    $contactEmail = Setting::get('contact_email');
    $contactAddress = Setting::get('contact_address');
    $contactSchedule = Setting::get('contact_schedule');
    $privacyPolicyUrl = Setting::get('privacy_policy_url');

    // Redes (C2): claves nuevas, no requieren migración — Setting ya es un
    // almacén clave/valor genérico. Ninguna sembrada todavía.
    $socialInstagram = Setting::get('social_instagram_url');
    $socialFacebook = Setting::get('social_facebook_url');
    $socialYoutube = Setting::get('social_youtube_url');
    $whatsappLink = filled($contactPhone) ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $contactPhone) : null;

    $hasSocial = filled($socialInstagram) || filled($socialFacebook) || filled($socialYoutube) || filled($whatsappLink);
    $hasAnyContactInfo = filled($contactPhone) || filled($contactEmail) || filled($contactAddress) || filled($contactSchedule) || $hasSocial;

    // C3: nunca coordenadas inventadas. El enlace real a Maps (por dirección,
    // no por un pin fijo) solo existe si hay dirección real en Setting.
    $mapsUrl = filled($contactAddress)
        ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($contactAddress)
        : null;

    $subjectOptions = __('site.contacto.form.subject_options');
    $faqItems = __('site.contacto.faq.items');
    $heroAttributes = __('site.contacto.hero.attributes');

    // Íconos: mismo estilo lineal (stroke-width 2, viewBox 24x24) que
    // home.blade.php, para no introducir un segundo lenguaje visual.
    $iconHeadset = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="2.5" y="13" width="4" height="6" rx="1.5"/><rect x="17.5" y="13" width="4" height="6" rx="1.5"/><path d="M20 19v1a3 3 0 0 1-3 3h-3"/></svg>';
    $iconShield = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg>';
    $iconLock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>';
    $iconMail = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>';
    $iconPin = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>';
    $iconPinLarge = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-9 w-9" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconChat = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path d="M12 2a9 9 0 0 0-7.8 13.5L3 22l6.7-1.2A9 9 0 1 0 12 2Z"/><path d="M8.5 9.2c.3 3.5 2.8 6 6.3 6.3"/></svg>';
    $iconChatSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M12 2a9 9 0 0 0-7.8 13.5L3 22l6.7-1.2A9 9 0 1 0 12 2Z"/><path d="M8.5 9.2c.3 3.5 2.8 6 6.3 6.3"/></svg>';
    $iconSend = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>';
    $iconExternal = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>';
    $iconInstagram = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6" fill="currentColor" stroke="none"/></svg>';
    $iconFacebook = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M14 21v-7h3l1-4h-4V7.5A1.5 1.5 0 0 1 15.5 6H18V3h-3a4.5 4.5 0 0 0-4.5 4.5V10H8v4h2.5v7Z"/></svg>';
    $iconYoutube = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="3"/><path d="m10 9 5 3-5 3V9Z" fill="currentColor" stroke="none"/></svg>';

    $heroIcons = [$iconHeadset, $iconShield, $iconLock];
@endphp
<x-layout title="{{ __('site.nav.contact') }}">

    {{-- ============ MIGAS DE PAN + HERO PARTIDO ============ --}}
    {{--
        C8: hero partido (2 columnas, foto confinada a la mitad derecha),
        distinto del hero de ancho completo de la Home. El texto de marca
        ("aventura") cae sobre --surface, no sobre la foto: mismo caso ya
        documentado en docs/lote-1/00-sistema-diseno.md §2 para el hero de
        Home, no hace falta velo.
    --}}
    <section class="overflow-hidden bg-surface">
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <x-ui.breadcrumbs :items="[
                ['label' => __('site.contacto.breadcrumb.home'), 'href' => route('home')],
                ['label' => __('site.contacto.breadcrumb.current')],
            ]" />
        </div>

        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-12 lg:px-8 lg:py-16">
            <div>
                <x-ui.eyebrow class="mb-5">{{ __('site.contacto.hero.eyebrow') }}</x-ui.eyebrow>

                <h1 class="font-display text-3xl font-semibold leading-[1.15] text-ink sm:text-4xl lg:text-5xl">
                    {{ __('site.contacto.hero.title_before') }}
                    <span class="text-brand-text">{{ __('site.contacto.hero.title_highlight') }}</span>
                </h1>

                <p class="mt-4 max-w-xl text-base text-text-2 sm:text-lg">
                    {{ __('site.contacto.hero.subtitle') }}
                </p>

                <div class="mt-8 grid gap-5 sm:grid-cols-3">
                    @foreach($heroAttributes as $i => $attribute)
                        <x-ui.hero-attribute
                            :icon="$heroIcons[$i] ?? $iconHeadset"
                            :title="$attribute['title']"
                            :description="$attribute['description']"
                        />
                    @endforeach
                </div>
            </div>

            <div class="aspect-[4/5] w-full overflow-hidden rounded-3xl bg-surface-2 sm:aspect-[16/9] lg:aspect-[4/5]">
                <img
                    src="{{ PlaceholderImage::svg(1000, 1250, 'Foto de contacto (pendiente)', '1b6949') }}"
                    alt="{{ __('site.contacto.hero.photo_alt') }}"
                    width="1000" height="1250"
                    class="h-full w-full object-cover"
                >
            </div>
        </div>
    </section>

    {{-- ============ FORMULARIO + INFORMACIÓN DE CONTACTO ============ --}}
    <section class="bg-ground">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[3fr_2fr] lg:px-8 lg:py-16">

            <div class="rounded-3xl border border-line bg-surface p-6 shadow-sm sm:p-8">
                <x-ui.section-title as="h2">{{ __('site.contacto.form.title') }}</x-ui.section-title>
                <p class="-mt-4 mb-6 text-sm text-text-2">{{ __('site.contacto.form.description') }}</p>

                @if(session('contact_success'))
                    <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800" role="status">
                        {{ __('contact-form.flash.success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 rounded-xl border border-danger/30 bg-danger/5 p-4 text-sm text-danger" role="alert">
                        {{ __('contact-form.flash.error_summary') }}
                    </div>
                @endif

                {{--
                    C1 resuelto (lote 1, alcance adelantado por Anyerson el
                    2026-09-02): App\Http\Controllers\ContactMessageController
                    y contact_messages ya existen. "website" es el honeypot
                    (antispam sin CAPTCHA de terceros): oculto de verdad
                    (position:absolute fuera de pantalla, no display:none —
                    algunos bots ignoran los display:none), fuera del tab
                    order y sin autocompletar. Un humano nunca lo llena; un
                    bot que sí lo hace recibe un "éxito" fingido en el
                    controller, nunca un error que le confirme que fue
                    detectado.
                --}}
                <form method="POST" action="{{ route('contact.store') }}" novalidate class="grid gap-5">
                    @csrf

                    <div aria-hidden="true" style="position:absolute; left:-9999px; top:-9999px;">
                        <label for="field-website">Deja este campo vacío</label>
                        <input type="text" id="field-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.form.input
                            name="name"
                            :label="__('site.contacto.form.name_label')"
                            :placeholder="__('site.contacto.form.name_placeholder')"
                            :value="old('name')"
                            :error="$errors->first('name')"
                            required
                        />
                        <x-ui.form.input
                            name="email"
                            type="email"
                            :label="__('site.contacto.form.email_label')"
                            :placeholder="__('site.contacto.form.email_placeholder')"
                            :value="old('email')"
                            :error="$errors->first('email')"
                            required
                        />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.form.input
                            name="phone"
                            type="tel"
                            :label="__('site.contacto.form.phone_label')"
                            :placeholder="__('site.contacto.form.phone_placeholder')"
                            :value="old('phone')"
                            :error="$errors->first('phone')"
                        />
                        <x-ui.form.select
                            name="subject"
                            :label="__('site.contacto.form.subject_label')"
                            :placeholder="__('site.contacto.form.subject_placeholder')"
                            :options="$subjectOptions"
                            :value="old('subject')"
                            :error="$errors->first('subject')"
                            required
                        />
                    </div>

                    <x-ui.form.textarea
                        name="message"
                        :label="__('site.contacto.form.message_label')"
                        :placeholder="__('site.contacto.form.message_placeholder')"
                        :error="$errors->first('message')"
                        required
                    >{{ old('message') }}</x-ui.form.textarea>

                    {{--
                        C4: la casilla va asociada de verdad a su etiqueta
                        (x-ui.form.checkbox usa <label for>). El enlace a la
                        política de privacidad solo existe si hay una URL
                        real en Setting (privacy_policy_url, la misma que ya
                        usa el footer) — sin eso, texto plano, nunca un "#".
                        Ley 29733 (protección de datos personales, Perú): el
                        controller guarda cuándo se aceptó, no solo que la
                        validación pasó.
                    --}}
                    <x-ui.form.checkbox name="privacy" required :error="$errors->first('privacy')">
                        {{ __('site.contacto.form.privacy_pre') }}
                        @if(filled($privacyPolicyUrl))
                            <a
                                href="{{ $privacyPolicyUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-brand-text underline underline-offset-2 hover:text-action-hover"
                            >{{ __('site.contacto.form.privacy_link') }}</a>
                        @else
                            <span class="font-medium text-text-muted">{{ __('site.contacto.form.privacy_pending') }}</span>
                        @endif
                        {{ __('site.contacto.form.privacy_post') }}
                    </x-ui.form.checkbox>

                    <div>
                        {{--
                            R2 (ronda 2, arreglo 1): x-ui.button ya acepta
                            `type` (button|submit|reset, default "button"),
                            así que vuelve a usarse el componente en vez del
                            <button> nativo con clases duplicadas a mano.
                        --}}
                        <x-ui.button type="submit" :icon="$iconSend">
                            {{ __('site.contacto.form.submit') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>

            <div class="rounded-3xl border border-line bg-surface p-6 shadow-sm sm:p-8">
                <x-ui.section-title as="h2">{{ __('site.contacto.info.title') }}</x-ui.section-title>

                @if($hasAnyContactInfo)
                    <div class="grid gap-4">
                        <x-ui.contact-info-card :icon="$iconPhone" :title="__('site.contacto.info.phone_title')" :has="filled($contactPhone)">
                            <a href="tel:{{ preg_replace('/\s+/', '', (string) $contactPhone) }}" class="font-medium text-brand-text hover:underline">
                                {{ $contactPhone }}
                            </a>
                        </x-ui.contact-info-card>

                        <x-ui.contact-info-card :icon="$iconMail" :title="__('site.contacto.info.email_title')" :has="filled($contactEmail)">
                            <a href="mailto:{{ $contactEmail }}" class="font-medium text-brand-text hover:underline">
                                {{ $contactEmail }}
                            </a>
                        </x-ui.contact-info-card>

                        <x-ui.contact-info-card :icon="$iconPin" :title="__('site.contacto.info.address_title')" :has="filled($contactAddress)">
                            {{ $contactAddress }}
                        </x-ui.contact-info-card>

                        <x-ui.contact-info-card :icon="$iconClock" :title="__('contact-form.info.schedule_title')" :has="filled($contactSchedule)">
                            {{ $contactSchedule }}
                        </x-ui.contact-info-card>

                        <x-ui.contact-info-card :icon="$iconChat" :title="__('site.contacto.info.social_title')" :has="$hasSocial">
                            <div class="mt-1 flex flex-wrap gap-2">
                                @if(filled($socialInstagram))
                                    <a href="{{ $socialInstagram }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_instagram') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-action hover:bg-action hover:text-on-action">{!! $iconInstagram !!}</a>
                                @endif
                                @if(filled($socialFacebook))
                                    <a href="{{ $socialFacebook }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_facebook') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-action hover:bg-action hover:text-on-action">{!! $iconFacebook !!}</a>
                                @endif
                                @if(filled($socialYoutube))
                                    <a href="{{ $socialYoutube }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_youtube') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-action hover:bg-action hover:text-on-action">{!! $iconYoutube !!}</a>
                                @endif
                                @if(filled($whatsappLink))
                                    <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_whatsapp') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-action hover:bg-action hover:text-on-action">{!! $iconChatSmall !!}</a>
                                @endif
                            </div>
                        </x-ui.contact-info-card>
                    </div>
                @else
                    <p class="text-sm text-text-2">{{ __('site.contacto.info.empty') }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ============ PREGUNTAS FRECUENTES + DÓNDE ESTAMOS ============ --}}
    <section class="bg-surface">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-16">
            <div>
                <x-ui.section-title as="h2">{{ __('site.contacto.faq.title') }}</x-ui.section-title>

                <div>
                    @foreach($faqItems as $item)
                        <x-ui.faq-item :question="$item['question']">{{ $item['answer'] }}</x-ui.faq-item>
                    @endforeach
                </div>

                @if($whatsappLink)
                    <div class="mt-6 flex flex-col items-start gap-4 rounded-2xl bg-brand-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface text-action" aria-hidden="true">
                                {!! $iconHeadset !!}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-ink">{{ __('site.contacto.help.title') }}</p>
                                <p class="text-xs text-text-2">{{ __('site.contacto.help.description') }}</p>
                            </div>
                        </div>
                        <x-ui.button :href="$whatsappLink" target="_blank" rel="noopener noreferrer" size="sm" class="w-full justify-center sm:w-auto">
                            {{ __('site.contacto.help.cta') }}
                        </x-ui.button>
                    </div>
                @endif
            </div>

            <div>
                <x-ui.section-title as="h2">{{ __('site.contacto.map.title') }}</x-ui.section-title>

                {{--
                    C3: sin iframe de Google Maps. El pin del mockup apunta a
                    un punto de Cusco que no es de la clienta, y un iframe
                    mete un tercero que puede chocar con la CSP en producción
                    (ya bloqueó analítica en otro proyecto del estudio). Se
                    resuelve con un marcador de posición estático (sin red) y
                    un enlace real a Maps por dirección — nunca por
                    coordenadas inventadas. Ver docs/lote-1/00-sistema-diseno.md
                    §C3 para qué falta para activar un mapa embebido real.
                --}}
                <div class="flex aspect-[4/3] items-center justify-center rounded-3xl border border-dashed border-line bg-surface-2 text-text-muted">
                    <div class="flex flex-col items-center gap-2 px-6 text-center">
                        {!! $iconPinLarge !!}
                        <span class="text-xs">{{ __('site.contacto.map.placeholder_alt') }}</span>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-line bg-surface p-4 sm:flex-row sm:items-center sm:justify-between">
                    @if($mapsUrl)
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-action" aria-hidden="true">{!! $iconPin !!}</span>
                            <div>
                                <p class="text-sm font-semibold text-ink">{{ __('site.contacto.map.visit_us') }}</p>
                                <p class="text-sm text-text-2">{{ $contactAddress }}</p>
                            </div>
                        </div>
                        <x-ui.button :href="$mapsUrl" target="_blank" rel="noopener noreferrer" variant="secondary" size="sm" :icon="$iconExternal" class="w-full justify-center sm:w-auto">
                            {{ __('site.contacto.map.cta') }}
                            <span class="sr-only">{{ __('site.contacto.map.cta_new_tab') }}</span>
                        </x-ui.button>
                    @else
                        <p class="text-sm text-text-2">{{ __('site.contacto.map.missing') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

</x-layout>
