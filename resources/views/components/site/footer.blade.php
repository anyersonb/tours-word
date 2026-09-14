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
</footer>
