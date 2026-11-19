@php
    use App\Models\Destination;
    use App\Models\Setting;

    /**
     * Footer de INKA PALACE Travel (PDF s10): cinta andina + bloque café con
     * logo, destinos, enlaces, contacto y redes + franja de copyright y medios
     * de pago. Conserva lo que ya hacía el de main:
     *  - A6 (bloqueantes de producción peruanos): RUC, razón social, RNAVT,
     *    afiche ESNNA, libro de reclamaciones, políticas. Cada dato aparece
     *    SOLO si su Setting está cargado; nunca un relleno publicable. El
     *    sello "Certificados por MINCETUR" sale de x-ui.mincetur-badge, que
     *    tampoco imprime nada sin número RNAVT.
     *  - Una columna sin ningún dato cargado se oculta entera.
     *  - route() con prefijo de idioma.
     * Contacto: lo de Configuración (CMS) manda; sin él, el dato del diseño
     * (config/brand.php). Redes: sin URL entregada = texto sin enlace.
     */
    $companyRuc = Setting::get('company_ruc');
    $companyName = Setting::get('company_legal_name');
    $rnavtNumber = Setting::get('rnavt_number');
    $esnnaPosterUrl = Setting::get('esnna_poster_url');
    $complaintsBookUrl = Setting::get('complaints_book_url');
    $privacyPolicyUrl = Setting::get('privacy_policy_url');
    $cancellationPolicyUrl = Setting::get('cancellation_policy_url');

    $hasLegalBlock = filled($companyRuc) || filled($rnavtNumber) || filled($esnnaPosterUrl)
        || filled($complaintsBookUrl) || filled($privacyPolicyUrl) || filled($cancellationPolicyUrl);

    $contactPhone = Setting::get('contact_phone') ?: config('brand.contact.phone');
    $contactEmail = Setting::get('contact_email') ?: config('brand.contact.email');
    $contactAddress = Setting::get('contact_address') ?: config('brand.contact.address');
    $waUrl = \App\Support\WhatsApp::url(__('site.whatsapp.default_text'));

    $socials = [
        'tripadvisor' => ['Tripadvisor', config('brand.social.tripadvisor')],
        'viator' => ['Viator', config('brand.social.viator')],
        'youtube' => ['YouTube', Setting::get('social_youtube_url') ?: config('brand.social.youtube')],
        'instagram' => ['Instagram', Setting::get('social_instagram_url') ?: config('brand.social.instagram')],
        'facebook' => ['Facebook', Setting::get('social_facebook_url') ?: config('brand.social.facebook')],
        'tiktok' => ['TikTok', config('brand.social.tiktok')],
    ];

    // Destinos publicados del CMS (máx. 5), con su URL pública real. Sin
    // caché: son 5 filas y no hay invalidación automática todavía.
    $footerDestinations = Destination::query()->published()->ordered()->orderBy('id')->limit(5)->get();
@endphp

<x-home.ribbon />
<footer class="footer" id="contacto">
    <div class="container footer__grid">
        <div class="footer__brand">
            <a class="footer__logo" href="{{ route('home') }}" aria-label="{{ config('brand.name') }}">
                <x-brand.mark variant="full" sizes="108px" :eager="false" />
            </a>
            <p class="footer__about">{{ __('home.footer.about') }}</p>
            <a class="btn btn--wa" href="{{ $waUrl }}" target="_blank" rel="noopener"><x-glyph name="whatsapp" :size="18" />{{ __('home.footer.whatsapp_cta') }}</a>
        </div>

        <nav class="footer__col" aria-labelledby="f-dest">
            <h2 class="footer__h" id="f-dest">{{ __('home.footer.destinations') }}</h2>
            <ul>
                @forelse ($footerDestinations as $d)
                    <li><a href="{{ route('destinations.show', ['slug' => $d->slug]) }}">{{ $d->name }}</a></li>
                @empty
                    <li><a href="{{ route('destinations.index') }}">{{ __('site.nav.destinations') }}</a></li>
                @endforelse
            </ul>
        </nav>

        <nav class="footer__col" aria-labelledby="f-links">
            <h2 class="footer__h" id="f-links">{{ __('home.footer.links') }}</h2>
            <ul>
                <li><a href="{{ route('tours.index') }}">{{ __('home.footer.link_items.tours') }}</a></li>
                <li><a href="{{ route('experiences.index') }}">{{ __('home.footer.link_items.experiences') }}</a></li>
                <li><a href="{{ route('about') }}">{{ __('home.footer.link_items.about') }}</a></li>
                <li><a href="{{ route('contact') }}#preguntas">{{ __('home.footer.link_items.faq') }}</a></li>
                <li><a href="{{ route('contact') }}">{{ __('home.footer.link_items.contact') }}</a></li>
            </ul>
        </nav>

        <div class="footer__col">
            <h2 class="footer__h">{{ __('home.footer.contact') }}</h2>
            <ul class="footer__contact">
                @if (filled($contactPhone))
                    <li><x-glyph name="whatsapp" :size="16" /><a href="{{ $waUrl }}">{{ $contactPhone }}</a></li>
                @endif
                @if (filled($contactEmail))
                    <li><x-glyph name="mail" :size="16" /><a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></li>
                @endif
                @if (filled($contactAddress))
                    <li><x-glyph name="map-pin" :size="16" /><span>{{ $contactAddress }}</span></li>
                @endif
                <li><x-glyph name="globe" :size="16" /><a href="{{ config('brand.contact.website_url') }}">{{ config('brand.contact.website') }}</a></li>
            </ul>
            <ul class="footer__social" aria-label="{{ __('site.contacto.info.social_title') }}">
                @foreach ($socials as [$label, $url])
                    <li>
                        @if (filled($url))
                            <a class="pill-link" href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>
                        @else
                            {{-- Sin URL entregada por la clienta: no se inventa el enlace. --}}
                            <span class="pill-link pill-link--off">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($hasLegalBlock)
        <div class="container footer__legal">
            <x-ui.mincetur-badge />
            @if (filled($companyRuc))<span>RUC {{ $companyRuc }}</span>@endif
            @if (filled($companyName))<span>{{ $companyName }}</span>@endif
            @if (filled($esnnaPosterUrl))<a href="{{ $esnnaPosterUrl }}">Afiche ESNNA</a>@endif
            @if (filled($privacyPolicyUrl))<a href="{{ $privacyPolicyUrl }}">{{ __('site.footer.privacy_policy') }}</a>@endif
            @if (filled($cancellationPolicyUrl))<a href="{{ $cancellationPolicyUrl }}">{{ __('site.footer.cancellation_policy') }}</a>@endif
            @if (filled($complaintsBookUrl))<a href="{{ $complaintsBookUrl }}">{{ __('site.footer.complaints_book') }}</a>@endif
        </div>
    @endif

    <div class="container footer__bottom">
        <p>&copy; {{ now()->year }} {{ $companyName ?: config('brand.name') }}. {{ __('home.footer.rights') }} {{ config('brand.contact.instagram_handle') }}</p>
        @if (\App\Support\Placeholder::visible(__('home.footer.payments_placeholder')))
            <p class="footer__pay">{{ __('home.footer.payments_label') }} <span class="chip-ghost">{{ __('home.footer.payments_placeholder') }}</span></p>
        @endif
    </div>
</footer>
