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
@endphp
<footer class="border-t border-line bg-surface">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-brand.mark variant="horizontal" class="h-7 w-auto" />
                @if(filled($companyName))
                    <p class="mt-3 text-sm text-text-2">{{ $companyName }}</p>
                @endif
            </div>

            <nav aria-label="{{ __('site.footer.quick_links') }}">
                <h3 class="mb-3 text-sm font-semibold text-ink">{{ __('site.footer.quick_links') }}</h3>
                <ul class="space-y-2 text-sm text-text-2">
                    <li><a href="{{ route('home') }}" class="hover:text-action">{{ __('site.nav.home') }}</a></li>
                    <li><a href="{{ Route::has('tours.index') ? route('tours.index') : '#' }}" class="hover:text-action">{{ __('site.nav.tours') }}</a></li>
                    <li><a href="{{ Route::has('about') ? route('about') : '#' }}" class="hover:text-action">{{ __('site.nav.about') }}</a></li>
                    <li><a href="{{ Route::has('contact') ? route('contact') : '#' }}" class="hover:text-action">{{ __('site.nav.contact') }}</a></li>
                </ul>
            </nav>

            <div>
                <h3 class="mb-3 text-sm font-semibold text-ink">{{ __('site.footer.information') }}</h3>
                <ul class="space-y-2 text-sm text-text-2">
                    @if(filled($privacyPolicyUrl))
                        <li><a href="{{ $privacyPolicyUrl }}" class="hover:text-action">{{ __('site.footer.privacy_policy') }}</a></li>
                    @endif
                    @if(filled($cancellationPolicyUrl))
                        <li><a href="{{ $cancellationPolicyUrl }}" class="hover:text-action">{{ __('site.footer.cancellation_policy') }}</a></li>
                    @endif
                    @if(filled($complaintsBookUrl))
                        <li><a href="{{ $complaintsBookUrl }}" class="hover:text-action">{{ __('site.footer.complaints_book') }}</a></li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold text-ink">{{ __('site.footer.contact') }}</h3>
                <ul class="space-y-2 text-sm text-text-2">
                    @if(filled($contactPhone))
                        <li><a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}" class="hover:text-action">{{ $contactPhone }}</a></li>
                    @endif
                    @if(filled($contactEmail))
                        <li><a href="mailto:{{ $contactEmail }}" class="hover:text-action">{{ $contactEmail }}</a></li>
                    @endif
                    @if(filled($contactAddress))
                        <li>{{ $contactAddress }}</li>
                    @endif
                </ul>
            </div>
        </div>

        @if($hasLegalBlock || filled($rnavtNumber))
            <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-line-soft pt-6 text-xs text-text-muted">
                @if(filled($companyRuc))
                    <span>RUC {{ $companyRuc }}</span>
                @endif
                @if(filled($rnavtNumber))
                    <span>RNAVT {{ $rnavtNumber }}</span>
                @endif
                @if(filled($esnnaPosterUrl))
                    <a href="{{ $esnnaPosterUrl }}" class="hover:text-action">Afiche ESNNA</a>
                @endif
            </div>
        @endif

        <div class="mt-6 border-t border-line-soft pt-6 text-sm text-text-muted">
            &copy; {{ now()->year }} {{ $companyName ?: config('app.name') }}. {{ __('site.footer.rights') }}
        </div>
    </div>
</footer>
