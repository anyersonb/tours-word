@php
    use App\Models\Setting;

    /**
     * A6: bloqueantes de producción peruanos. Ninguno de estos datos existe
     * todavía (Anyerson se los está pidiendo a la clienta) — cada bloque se
     * oculta solo si su Setting está vacío. Nunca un placeholder publicable
     * ("RUC 20XXXXXXXXX" de relleno, por ejemplo).
     */
    $companyRuc = Setting::get('company_ruc');
    $companyName = Setting::get('company_legal_name');
    $rnavtNumber = Setting::get('rnavt_number');
    $esnnaPosterUrl = Setting::get('esnna_poster_url');
    $complaintsBookUrl = Setting::get('complaints_book_url');
    $privacyPolicyUrl = Setting::get('privacy_policy_url');
    $cancellationPolicyUrl = Setting::get('cancellation_policy_url');
    $contactPhone = Setting::get('contact_phone');
    $contactEmail = Setting::get('contact_email');
    $contactAddress = Setting::get('contact_address');

    $hasLegalBlock = filled($companyRuc) || filled($companyName) || filled($rnavtNumber) || filled($esnnaPosterUrl);

    // Objetivo 5 (lote i18n): una columna con encabezado y una lista sin
    // ningun elemento es un defecto visible, la misma familia de fallo que
    // un bloque legal vacio (linea 21). No se oculta el @if item por item:
    // se oculta la COLUMNA completa si NINGUNO de sus datos esta cargado.
    $hasInformationColumn = filled($privacyPolicyUrl) || filled($cancellationPolicyUrl) || filled($complaintsBookUrl);
    $hasContactColumn = filled($contactPhone) || filled($contactEmail) || filled($contactAddress);

    // Port Roavio (2026-09-20): fila de redes en el pie. Mismos Settings que
    // ya usa contact.blade.php (site.contacto.info) — ningún dato nuevo,
    // ningún icono sin su URL real detrás.
    $socialInstagram = Setting::get('social_instagram_url');
    $socialFacebook = Setting::get('social_facebook_url');
    $socialYoutube = Setting::get('social_youtube_url');
    $footerWhatsapp = filled($contactPhone) ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $contactPhone) : null;
    $hasFooterSocial = filled($socialInstagram) || filled($socialFacebook) || filled($socialYoutube) || filled($footerWhatsapp);
    $iconInstagram = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6" fill="currentColor" stroke="none"/></svg>';
    $iconFacebook = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M14 21v-7h3l1-4h-4V7.5A1.5 1.5 0 0 1 15.5 6H18V3h-3a4.5 4.5 0 0 0-4.5 4.5V10H8v4h2.5v7Z"/></svg>';
    $iconYoutube = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="3"/><path d="m10 9 5 3-5 3V9Z" fill="currentColor" stroke="none"/></svg>';
    $iconChatSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M12 2a9 9 0 0 0-7.8 13.5L3 22l6.7-1.2A9 9 0 1 0 12 2Z"/><path d="M8.5 9.2c.3 3.5 2.8 6 6.3 6.3"/></svg>';
@endphp
{{--
    Pase visual 2026-09-14: el pie iba sobre --surface (blanco) igual que la
    última sección de casi todas las pantallas, así que no cerraba nada — la
    página simplemente se quedaba sin contenido. Ahora va sobre
    --ink-surface, la superficie oscura de marca. Contrastes medidos con la
    fórmula WCAG sobre ese fondo: blanco 15.59:1, --on-dark-2 10.32:1,
    --on-dark-3 6.07:1. Los tres pasan AA de texto normal.
--}}
<footer class="weave-dark bg-ink-surface text-on-dark-2">
    <div class="shell py-14 lg:py-16">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
            <div class="sm:col-span-2 lg:col-span-1">
                <x-brand.mark variant="mono" class="h-7 w-auto text-white" />
                @if(filled($companyName))
                    <p class="mt-4 text-sm text-on-dark-2">{{ $companyName }}</p>
                @endif
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-on-dark-3">
                    {{ __('site.seo.default_description') }}
                </p>

                {{-- Port Roavio: fila de redes bajo la descripción. --}}
                @if($hasFooterSocial)
                    <div class="mt-5 flex gap-2">
                        @if(filled($socialInstagram))
                            <a href="{{ $socialInstagram }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_instagram') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-action">{!! $iconInstagram !!}</a>
                        @endif
                        @if(filled($socialFacebook))
                            <a href="{{ $socialFacebook }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_facebook') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-action">{!! $iconFacebook !!}</a>
                        @endif
                        @if(filled($socialYoutube))
                            <a href="{{ $socialYoutube }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_youtube') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-action">{!! $iconYoutube !!}</a>
                        @endif
                        @if(filled($footerWhatsapp))
                            <a href="{{ $footerWhatsapp }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('site.contacto.info.social_whatsapp') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-action">{!! $iconChatSmall !!}</a>
                        @endif
                    </div>
                @endif
            </div>

            <nav aria-label="{{ __('site.footer.quick_links') }}">
                <h3 class="eyebrow mb-4 text-on-dark-3">{{ __('site.footer.quick_links') }}</h3>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.nav.home') }}</a></li>
                    <li><a href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.nav.tours') }}</a></li>
                    <li><a href="{{ Route::has('destinations.index') ? route('destinations.index') : '#' }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.nav.destinations') }}</a></li>
                    <li><a href="{{ Route::has('experiences.index') ? route('experiences.index') : '#' }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.nav.experiences') }}</a></li>
                    <li><a href="{{ Route::has('about') ? route('about') : '#' }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.nav.about') }}</a></li>
                    <li><a href="{{ Route::has('contact') ? route('contact') : '#' }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.nav.contact') }}</a></li>
                </ul>
            </nav>

            @if($hasInformationColumn)
                <div>
                    <h3 class="eyebrow mb-4 text-on-dark-3">{{ __('site.footer.information') }}</h3>
                    <ul class="space-y-2.5 text-sm">
                        @if(filled($privacyPolicyUrl))
                            <li><a href="{{ $privacyPolicyUrl }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.footer.privacy_policy') }}</a></li>
                        @endif
                        @if(filled($cancellationPolicyUrl))
                            <li><a href="{{ $cancellationPolicyUrl }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.footer.cancellation_policy') }}</a></li>
                        @endif
                        @if(filled($complaintsBookUrl))
                            <li><a href="{{ $complaintsBookUrl }}" class="text-on-dark-2 transition-colors hover:text-white">{{ __('site.footer.complaints_book') }}</a></li>
                        @endif
                    </ul>
                </div>
            @endif

            @if($hasContactColumn)
                <div>
                    <h3 class="eyebrow mb-4 text-on-dark-3">{{ __('site.footer.contact') }}</h3>
                    <ul class="space-y-2.5 text-sm">
                        @if(filled($contactPhone))
                            <li><a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}" class="text-on-dark-2 transition-colors hover:text-white">{{ $contactPhone }}</a></li>
                        @endif
                        @if(filled($contactEmail))
                            <li><a href="mailto:{{ $contactEmail }}" class="text-on-dark-2 transition-colors hover:text-white">{{ $contactEmail }}</a></li>
                        @endif
                        @if(filled($contactAddress))
                            <li class="text-on-dark-2">{{ $contactAddress }}</li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>

        @if($hasLegalBlock || filled($rnavtNumber))
            <div class="mt-12 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-on-dark-line pt-6 text-xs text-on-dark-3">
                @if(filled($companyRuc))
                    <span>RUC {{ $companyRuc }}</span>
                @endif
                @if(filled($rnavtNumber))
                    <span>RNAVT {{ $rnavtNumber }}</span>
                @endif
                @if(filled($esnnaPosterUrl))
                    <a href="{{ $esnnaPosterUrl }}" class="transition-colors hover:text-white">Afiche ESNNA</a>
                @endif
            </div>
        @endif

        <div class="mt-6 border-t border-on-dark-line pt-6 text-sm text-on-dark-3">
            &copy; {{ now()->year }} {{ $companyName ?: config('app.name') }}. {{ __('site.footer.rights') }}
        </div>
    </div>

    {{--
        Port Roavio: wordmark gigante que cierra el pie. Mismo SVG de marca
        (inline, reacciona a --brand-h — ver docblock de x-brand.mark), solo
        que a un tamaño que nadie más usa en el sitio. Ancho relativo (no
        alto fijo) para que escale proporcional al viewport vía su propio
        viewBox, sin desbordar ni recortarse a los lados en pantallas
        angostas — acotado al mismo max-width que el resto del pie (80rem)
        para que su borde quede alineado con las columnas de arriba.
        aria-hidden: es decorativo, el nombre ya está en el <h1>/logo del
        header y en el primer bloque de esta misma columna.
    --}}
    <div class="mt-10 overflow-hidden border-t border-on-dark-line py-8 sm:py-10" aria-hidden="true">
        <x-brand.mark variant="mono" class="mx-auto block w-[88%] max-w-5xl text-white/90" />
    </div>
</footer>
