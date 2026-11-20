@php
    use App\Models\Setting;
    use App\Support\Placeholder;
    use App\Support\WhatsApp;
    use Illuminate\Support\Str;

    /*
     * Home de INKA PALACE Travel — port del PDF de diseño (docs/lote-1/inka-palace/
     * referencia/s00..s10.jpg, 1440 px) a main. Contrato de datos:
     * docs/lote-1/inka-palace/02-contrato-home.md (HomeController).
     *
     * Regla de composición: lo real (CMS) va primero; si faltan elementos
     * para completar la composición del diseño se rellenan con tarjetas de
     * MUESTRA, marcadas visiblemente ("Muestra" + nota de sección). Cuando el
     * CMS tenga datos suficientes, las muestras desaparecen solas. Nada de
     * cifras, reseñas ni precios inventados: lo que falta es un [MARCADOR].
     *
     * Modo revisión (solo APP_ENV=local): /es/?muestra=1 ignora el CMS y pinta
     * la composición completa del diseño, para compararla contra el PDF.
     */
    if (app()->environment('local') && request()->boolean('muestra')) {
        $destinations = collect(); $experiences = collect(); $featuredTours = collect(); $planTours = collect();
    }
    $fx = config('brand.fixed_images');
    // Pase 25: interruptor único de marcadores (config brand.show_placeholders). Encendido = el Home
    // se pinta TAL CUAL el PDF (marcadores literales, muestras del diseño en cada slot, sin ocultar filas).
    // Apagado (producción) = comportamiento anterior.
    $ph = Placeholder::enabled();
    $tr = fn (string $key, string $fallback) => trans()->has($key) ? __($key) : $fallback;
    $samples = [];   // secciones que incluyen muestras
    // CRO C-1: los chips "Muestra" y las notas de muestra SOLO en modo revisión
    // (local + ?muestra=1). En el sitio público no se muestran nunca.
    $showSamples = app()->environment('local') && request()->boolean('muestra');

    // Solo las fotos PROVISIONALES (img/...) se comprueban en disco: una ruta de
    // brand.images sin archivo daría un 404. Las del CMS (storage) se confían.
    $okImg = function (?string $url): ?string {
        if (! $url) {
            return null;
        }
        $prefix = asset('img/');
        if (str_starts_with($url, $prefix)) {
            return is_file(public_path('img/'.substr($url, strlen($prefix)))) ? $url : null;
        }

        return $url;
    };
    $asset = fn (?string $path): ?string => $path ? asset($path) : null;
    $countLabel = fn ($n) => ($n ?? 0) > 0 && ! $ph ? trans_choice('home.destinations.tours_count', $n, ['count' => $n]) : Placeholder::visible(__('home.destinations.placeholder_count'));
    $short = fn (?string $text, int $n = 120): ?string => filled($text) ? Str::limit(trim(strip_tags($text)), $n) : null;

    /* ---- Destinos ---- */
    // Regla del Home: un destino sin imagen NO se muestra (ni en el bento ni en ciudades).
    $realDests = collect($destinations ?? [])
        ->map(fn ($d) => array_merge($d, ['sample' => false, 'image' => $okImg($d['image'] ?? null), 'tagline' => $short($d['tagline'] ?? null)]))
        ->filter(fn ($d) => filled($d['image']))
        ->values();
    $bentoOrder = ['machu-picchu', 'cusco', 'valle-sagrado', 'ica', 'humantay'];
    $sampleDests = collect([
        ['name' => 'Machu Picchu', 'key' => 'machu-picchu'], ['name' => 'Cusco', 'key' => 'cusco'],
        ['name' => 'Valle Sagrado', 'key' => 'valle-sagrado'], ['name' => 'Ica', 'key' => 'ica'],
        ['name' => 'Humantay', 'key' => 'humantay'], ['name' => 'Lima', 'key' => 'lima'], ['name' => 'Nazca', 'key' => 'nazca'],
    ])->map(fn ($d) => $d + ['slug' => $d['key'], 'image' => $okImg($asset(config('brand.images.'.$d['key']))), 'tagline' => $tr('home.destinations.taglines.'.$d['key'], ''), 'city_tagline' => $tr('home.cities.taglines.'.$d['key'], ''), 'tours_count' => null, 'url' => null, 'sample' => true]);

    // Reales con foto primero; se completan hasta 5 con los del diseño en el orden del PDF
    // (solo los que tengan imagen en disco) y el conjunto se ordena como el PDF.
    $pool = $realDests->values();
    if ($ph) {
        // Como el PDF: mismos 5 destinos, misma foto y misma frase; solo se toma del CMS el enlace.
        $pool = collect($bentoOrder)->map(function ($k) use ($sampleDests, $realDests) {
            $s = $sampleDests->firstWhere('key', $k);
            $r = $realDests->firstWhere('key', $k);

            return $s ? array_merge($s, ['url' => $r['url'] ?? null, 'sample' => false]) : null;
        })->filter(fn ($d) => $d && filled($d['image']))->values();
    }
    foreach ($bentoOrder as $k) {
        if ($pool->count() >= 5) { break; }
        $s = $sampleDests->firstWhere('key', $k);
        if ($s && filled($s['image']) && ! $pool->contains('key', $k)) { $pool->push($s); }
    }
    $pool = $pool->take(5)->sortBy(fn ($d) => ($i = array_search($d['key'], $bentoOrder, true)) === false ? 99 : $i)->values();
    if ($pool->contains('sample', true)) { $samples[] = 'destinos'; }
    $featuredDest = $pool->firstWhere('key', 'machu-picchu') ?? $pool->first();
    $mosaic = $pool->reject(fn ($d) => $d['key'] === $featuredDest['key'])->take(4)->values();

    $cityOrder = ['ica', 'cusco', 'lima', 'nazca'];
    $cities = $realDests->filter(fn ($d) => in_array($d['key'], $cityOrder, true))->values();
    if ($ph) {
        $cities = collect($cityOrder)->map(function ($k) use ($sampleDests, $realDests) {
            $s = $sampleDests->firstWhere('key', $k);
            $r = $realDests->firstWhere('key', $k);

            return $s ? array_merge($s, ['url' => $r['url'] ?? null, 'sample' => false]) : null;
        })->filter(fn ($d) => $d && filled($d['image']))->values();
    }
    foreach ($sampleDests as $s) {
        if ($cities->count() >= 4) { break; }
        if (in_array($s['key'], $cityOrder, true) && filled($s['image']) && ! $cities->contains('key', $s['key'])) { $cities->push($s); }
    }
    $cities = $cities->sortBy(fn ($d) => array_search($d['key'], $cityOrder))->values();
    // Pill "N tours": solo si TODAS las tarjetas visibles tienen un conteo real (>0); si no, en ninguna.
    $showCityCounts = $ph || $cities->isNotEmpty() && $cities->every(fn ($c) => ($c['tours_count'] ?? 0) > 0);
    if ($cities->contains('sample', true)) { $samples[] = 'ciudades'; }

    /* ---- Experiencias ---- */
    $exps = collect($experiences ?? [])->take(3)->map(fn ($e) => array_merge($e, ['sample' => false, 'image' => $okImg($e['image'] ?? null), 'description' => $short($e['description'] ?? null, 110)]));
    if ($exps->isEmpty() || $ph) {
        $realExps = $exps->keyBy('key');
        $samples[] = 'experiencias';
        $exps = collect([['name' => 'Trekking', 'key' => 'trekking'], ['name' => 'Gastronomía', 'key' => 'gastronomia'], ['name' => 'Cultura', 'key' => 'cultura']])
            ->map(fn ($e) => $e + ['image' => $okImg($asset(config('brand.images.'.$e['key']))), 'description' => __('home.experiences.taglines.'.$e['key']), 'link_label' => __('home.experiences.links.'.$e['key']), 'url' => $realExps[$e['key']]['url'] ?? null, 'sample' => ! $ph]);
    }
    $expIcons = ['trekking' => ['landscape', 'forest'], 'gastronomia' => ['restaurant', 'gold'], 'cultura' => ['account-balance', 'teal']];

    /* ---- Tours destacados (4 tarjetas) ---- */
    $t = $fx['tours'];
    // Regla del Home: un tour sin foto no entra; se completa con los del diseño en el orden del PDF.
    $tours = collect($featuredTours ?? [])
        ->map(fn ($x) => $x + ['sample' => false, 'image' => $okImg($x['image'] ?? null)])
        ->filter(fn ($x) => filled($x['image']))
        ->take(4)->values();
    $realToursByKey = collect($featuredTours ?? [])->keyBy('key');
    $sampleTours = collect([
        ['key' => 'city-tour-lima', 'title' => 'City Tour Lima', 'destination_name' => 'Lima', 'duration_label' => '[N] horas', 'badge' => 'Recomendado', 'image' => $okImg($asset($t['city-tour-lima']))],
        ['key' => 'laguna-humantay', 'title' => 'Laguna Humantay', 'destination_name' => 'Cusco', 'duration_label' => '1 día', 'badge' => 'Tendencia', 'image' => $okImg($asset($t['laguna-humantay']))],
        ['key' => 'montana-7-colores', 'title' => 'Montaña de 7 Colores', 'destination_name' => 'Cusco', 'duration_label' => '1 día', 'badge' => 'Más vendido', 'image' => $okImg($asset($t['montana-7-colores']))],
        ['key' => 'machu-picchu', 'title' => 'Machu Picchu', 'destination_name' => 'Cusco', 'duration_label' => '[N] días', 'badge' => 'Últimos cupos', 'image' => $okImg($asset($t['machu-picchu']))],
    ])->map(fn ($x) => $x + ['price' => null, 'languages' => 'ES / EN', 'url' => $realToursByKey[$x['key']]['url'] ?? null, 'sample' => ! $ph])->filter(fn ($x) => filled($x['image']));
    if ($ph) { $tours = collect(); }   // como el PDF: los 4 tours del diseño, en su orden
    foreach ($sampleTours as $s) {
        if ($tours->count() >= 4) { break; }
        if (! $tours->contains('key', $s['key'])) { $tours->push($s); }
    }
    if ($tours->contains('sample', true)) { $samples[] = 'tours'; }

    /* ---- Planes por duración (1 por grupo) ---- */
    // Solo tours reales con foto y título publicable (el controller ya no deja que uno sin foto reserve el grupo).
    $plans = collect($ph ? [] : ($planTours ?? []))
        ->map(fn ($p) => $p + ['sample' => false, 'image' => $okImg($p['image'] ?? null)])
        ->filter(fn ($p) => filled($p['image']) && ! Placeholder::is(Placeholder::title($p['title'])))
        ->values();
    $samplePlans = [
        '4h' => ['title' => 'City Tour Lima', 'image' => $asset($fx['plans']['city-tour-lima'] ?? $t['city-tour-lima']), 'price' => null],
        '1dia' => ['title' => 'Montaña de 7 Colores', 'image' => $asset($fx['plans']['montana-7-colores'] ?? $t['montana-7-colores']), 'price' => null],
        '2-3dias' => ['title' => 'Ica y Nazca', 'image' => $asset($fx['plan_nazca']), 'price' => null],
        '4mas' => ['title' => 'Camino Inca a Machu Picchu, 4 días', 'image' => $asset($fx['camino_inca']), 'price' => $incaTrailPrice ?? null, 'price_usd' => $incaTrailPriceUsd ?? null],
    ];
    if ($ph) {
        // PDF s05: "Desde S/ [PRECIO]" en las tres primeras, "Desde S/ 3,500" en la última; el 3.º es un marcador de sugerencia.
        $samplePlans['4h']['price'] = 'S/ [PRECIO]';
        $samplePlans['1dia']['price'] = 'S/ [PRECIO]';
        $samplePlans['2-3dias'] = ['title' => '[Tour sugerido: Ica y Nazca]', 'image' => $asset($fx['plan_nazca']), 'price' => 'S/ [PRECIO]'];
        $samplePlans['4mas']['price'] = 'S/ 3,500';
        $samplePlans['4mas']['price_usd'] = null;
    }
    foreach ($samplePlans as $bucket => $sp) {
        if (! $plans->contains('bucket', $bucket)) {
            $plans->push($sp + ['bucket' => $bucket, 'bucket_label' => __('home.plans.filters.'.$bucket), 'bucket_kicker' => __('home.plans.bucket_kicker.'.$bucket), 'url' => null, 'sample' => ! $ph]);
        }
    }
    $order = ['4h', '1dia', '2-3dias', '4mas'];
    $plans = $plans->sortBy(fn ($p) => array_search($p['bucket'], $order))->values();
    if ($plans->contains('sample', true)) { $samples[] = 'planes'; }
    $planTone = ['4h' => 'teal', '1dia' => 'accent', '2-3dias' => 'forest', '4mas' => 'accent'];
    $sampleTag = fn (string $k) => $showSamples && in_array($k, $samples, true);
    $sampleNote = $tr('home.sample_note', 'Incluye tarjetas de muestra (marcadas) hasta que el CMS tenga contenido suficiente.');
    $sampleLabel = $tr('home.sample_chip', 'Muestra');
    $contactPhone = WhatsApp::phone();
    $contactEmail = Setting::get('contact_email') ?: config('brand.contact.email');
    $waUrl = WhatsApp::url(__('site.whatsapp.default_text'));
@endphp
<x-layout :title="__('home.meta.title')" :description="__('home.meta.description')" :title-literal="true">

    <x-home.hero :slides="$hero" />
    <x-home.search :destinations="$destinations ?? []" />
    <x-home.guarantees />

    {{-- ================= Destinos ================= --}}
    <section class="ip-sec ip-sec--dest" id="destinos" aria-labelledby="t-destinos">
        <div class="container">
            <div class="sec-head">
                <div class="sec-head__main">
                    <p class="ip-eyebrow">{{ __('home.destinations.eyebrow') }}</p>
                    <h2 class="sec-title" id="t-destinos">{{ __('home.destinations.title') }}</h2>
                    <p class="sec-text">{{ __('home.destinations.text') }}</p>
                </div>
                <x-home.link-more :href="route('destinations.index')">{{ __('home.destinations.see_all') }}</x-home.link-more>
            </div>
            @if ($sampleTag('destinos'))<p class="sample-tag">{{ $sampleNote }}</p>@endif

            <div class="mosaic">
                @php $d = $featuredDest; @endphp
                @if ($d)
                    <a class="dest dest--big" href="{{ $d['url'] ?? route('destinations.index') }}">
                        @if ($d['image'])<x-ui.picture :src="$d['image']" alt="" sizes="(min-width: 1024px) 34vw, 100vw" img-class="" />@endif
                        @if ($showSamples && ! empty($d['sample']))<span class="sample-chip">{{ $sampleLabel }}</span>@endif
                        <span class="badge badge--accent dest__badge">★ {{ __('home.destinations.most_wanted') }}</span>
                        <span class="dest__body">
                            <span class="dest__name">{{ $d['name'] }}</span>
                            @if ($d['tagline'])<span class="dest__tag">{{ $d['tagline'] }}</span>@endif
                            @php $dCount = $countLabel($d['tours_count'] ?? 0); $dFrom = ($incaTrailPrice ?? null) && $d['key'] === 'machu-picchu'; @endphp
                            @if ($dCount || $dFrom)<span class="dest__count">{{ $dCount }}@if ($dFrom){{ $dCount ? ', '.Str::lower(__('home.featured.from')) : __('home.featured.from') }} {{ $incaTrailPrice }}@endif</span>@endif
                        </span>
                        <span class="round-btn" aria-hidden="true"><x-glyph name="arrow-right" :size="18" /></span>
                    </a>
                @endif
                @foreach ($mosaic as $d)
                    <a class="dest dest--s{{ $loop->iteration }}" href="{{ $d['url'] ?? route('destinations.index') }}">
                        @if ($d['image'])<x-ui.picture :src="$d['image']" alt="" sizes="(min-width: 1024px) 24vw, 50vw" img-class="" />@endif
                        @if ($showSamples && ! empty($d['sample']))<span class="sample-chip">{{ $sampleLabel }}</span>@endif
                        <span class="dest__body">
                            <span class="dest__name">{{ $d['name'] }}</span>
                            @if ($d['tagline'])<span class="dest__tag">{{ $d['tagline'] }}</span>@endif
                        </span>
                        <span class="round-btn" aria-hidden="true"><x-glyph name="arrow-right" :size="18" /></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= Tours destacados + Camino Inca ================= --}}
    <section class="ip-sec" id="tours" aria-labelledby="t-tours">
        <div class="container">
            <div class="sec-head">
                <div class="sec-head__main">
                    <p class="ip-eyebrow">{{ __('home.featured.eyebrow') }}</p>
                    <h2 class="sec-title" id="t-tours">{{ __('home.featured.title') }}</h2>
                    <p class="sec-text">{{ __('home.featured.text') }}</p>
                </div>
                <x-home.link-more :href="route('tours.index')">{{ __('home.featured.see_all') }}</x-home.link-more>
            </div>
            @if ($sampleTag('tours'))<p class="sample-tag">{{ $sampleNote }}</p>@endif
            {{-- En móvil las tarjetas van en carrusel con la vecina asomando (mockups del cliente);
                 desde 1024 vuelven a la rejilla de 4 del PDF. --}}
            <div class="grid-4 grid-4--snap" tabindex="0" role="region" aria-label="{{ __('home.featured.title') }}">
                @foreach ($tours as $tour)<x-home.tour-card :tour="$tour" />@endforeach
            </div>
        </div>

        <div class="container inca-wrap" id="camino-inca">
            <div class="inca">
                <div class="inca__copy">
                    <p class="inca__badge"><x-glyph name="clock" :size="13" />{{ __('home.inca_trail.badge') }}</p>
                    <h2 class="inca__title">{{ __('home.inca_trail.title') }}</h2>
                    <p class="inca__text">{{ __('home.inca_trail.text') }}</p>
                    <ul class="inca__list">
                        @foreach (__('home.inca_trail.includes') as $inc)<li><x-glyph name="check" :size="14" />{{ $inc }}</li>@endforeach
                    </ul>
                    <div class="inca__actions">
                        <a class="btn btn--accent btn--lg" href="{{ route('contact') }}">{{ __('home.inca_trail.cta_primary') }}<x-glyph name="arrow-right" :size="18" /></a>
                        <a class="btn btn--ghost btn--lg" href="{{ $waUrl }}" target="_blank" rel="noopener"><x-glyph name="message" :size="16" />{{ __('home.inca_trail.cta_secondary') }}</a>
                    </div>
                </div>
                <div class="inca__photo">
                    <x-ui.picture :src="asset($fx['camino_inca'])" alt="" sizes="(min-width: 1024px) 46vw, 100vw" img-class="" />
                    @if (! empty($incaTrailPrice))
                        <p class="inca__price"><span>{{ __('home.inca_trail.price_label') }}</span>
                            <strong>
                                @if (! empty($incaTrailPriceUsd))
                                    <span x-data><span x-show="$store.currency.code === 'PEN'">{{ $incaTrailPrice }}</span><span x-show="$store.currency.code === 'USD'" x-cloak>{{ $incaTrailPriceUsd }}</span><noscript>{{ $incaTrailPrice }}</noscript></span>
                                @else
                                    {{ $incaTrailPrice }}
                                @endif
                            </strong>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ================= Tours por ciudad ================= --}}
    <section class="ip-sec ip-sec--alt" aria-labelledby="t-ciudad">
        <div class="container">
            <div class="sec-head">
                <div class="sec-head__main">
                    <p class="ip-eyebrow">{{ __('home.cities.eyebrow') }}</p>
                    <h2 class="sec-title" id="t-ciudad">{{ __('home.cities.title') }}</h2>
                    <p class="sec-text">{{ __('home.cities.text') }}</p>
                </div>
                <x-home.link-more :href="route('destinations.index')">{{ __('home.cities.see_all') }}</x-home.link-more>
            </div>
            @if ($sampleTag('ciudades'))<p class="sample-tag">{{ $sampleNote }}</p>@endif
            {{-- CRO A-4: en móvil/tablet, carrusel con la vecina asomando (como tours destacados). --}}
            <div class="grid-4 grid-4--cities grid-4--snap" tabindex="0" role="region" aria-label="{{ __('home.cities.title') }}">
                @foreach ($cities as $c)
                    <a class="city" href="{{ $c['url'] ?? route('destinations.index') }}">
                        @if ($c['image'])<x-ui.picture :src="$c['image']" alt="" sizes="(min-width: 1024px) 24vw, (min-width: 640px) 46vw, 100vw" img-class="" />@endif
                        @if ($showSamples && ! empty($c['sample']))<span class="sample-chip sample-chip--br">{{ $sampleLabel }}</span>@endif
                        @if ($showCityCounts && ($cCount = $countLabel($c['tours_count'] ?? 0)))<span class="count-pill">{{ $cCount }}</span>@endif
                        <span class="city__body">
                            <span class="dest__name">{{ $c['name'] }}</span>
                            @php $ct = ($c['city_tagline'] ?? null) ?: ($c['tagline'] ?? null); @endphp
                            @if ($ct)<span class="dest__tag">{{ $ct }}</span>@endif
                        </span>
                        <span class="round-btn" aria-hidden="true"><x-glyph name="arrow-right" :size="18" /></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= Experiencias ================= --}}
    <section class="ip-sec" id="experiencias" aria-labelledby="t-exp">
        <div class="container">
            <div class="sec-head">
                <div class="sec-head__main">
                    <p class="ip-eyebrow">{{ __('home.experiences.eyebrow') }}</p>
                    <h2 class="sec-title" id="t-exp">{{ __('home.experiences.title') }}</h2>
                    <p class="sec-text">{{ __('home.experiences.text') }}</p>
                </div>
            </div>
            @if ($sampleTag('experiencias'))<p class="sample-tag">{{ $sampleNote }}</p>@endif
            <div class="exp-grid">
                @foreach ($exps as $e)
                    @php [$ico, $tone] = $expIcons[$e['key'] ?? ''] ?? ['mountain', 'forest']; @endphp
                    <a class="exp exp--{{ $loop->iteration }}" href="{{ $e['url'] ?? route('experiences.index') }}">
                        @if ($e['image'])<x-ui.picture :src="$e['image']" alt="" sizes="(min-width: 1024px) 34vw, (min-width: 768px) 50vw, 100vw" img-class="" />@endif
                        @if ($showSamples && ! empty($e['sample']))<span class="sample-chip">{{ $sampleLabel }}</span>@endif
                        <span class="exp__body">
                            <span class="icon-tile icon-tile--solid-{{ $tone }}"><x-glyph :name="$ico" :size="22" /></span>
                            <span class="dest__name">{{ $e['name'] }}</span>
                            @if ($e['description'])<span class="dest__tag">{{ $e['description'] }}</span>@endif
                            <span class="exp__link">{{ $e['link_label'] }}<x-glyph name="arrow-right" :size="14" /></span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= Paisajes ================= --}}
    <section class="band" aria-labelledby="t-paisajes">
        <x-ui.picture :src="asset($fx['paisajes'])" alt="" sizes="100vw" img-class="band__bg" />
        <div class="band__shade" aria-hidden="true"></div>
        <div class="container band__inner">
            <p class="ip-eyebrow ip-eyebrow--dark">{{ __('home.landscapes.eyebrow') }}</p>
            <h2 class="band__title" id="t-paisajes">{{ __('home.landscapes.title') }}</h2>
            <p class="band__text">{{ __('home.landscapes.text') }}</p>
            <div class="band__actions">
                <a class="btn btn--accent btn--lg" href="{{ route('tours.index') }}">{{ __('home.landscapes.cta_primary') }}<x-glyph name="arrow-right" :size="18" /></a>
                <a class="btn btn--ghost btn--lg" href="{{ $waUrl }}" target="_blank" rel="noopener"><x-glyph name="message" :size="16" />{{ __('home.landscapes.cta_secondary') }}</a>
            </div>
            @php $bandStats = Placeholder::items(__('home.landscapes.stats')); @endphp
            {{-- Una sola cifra suelta se ve rota: la fila solo sale con 2 o más cifras reales. --}}
            @if (count($bandStats) >= 2 || ($ph && count($bandStats) > 0))
            <dl class="band__stats">
                @foreach ($bandStats as $s)
                    <div><dd>{{ $s['value'] }}</dd><dt>{{ $s['label'] }}</dt></div>
                @endforeach
            </dl>
            @endif
        </div>
    </section>
    <x-home.ribbon />

    {{-- ================= Planes por tiempo ================= --}}
    <section class="ip-sec ip-sec--dark" aria-labelledby="t-planes" x-data="{ f: 'all' }">
        <div class="container">
            <div class="sec-head sec-head--dark sec-head--filters">
                <div class="sec-head__main">
                    <p class="ip-eyebrow ip-eyebrow--dark">{{ __('home.plans.eyebrow') }}</p>
                    <h2 class="sec-title" id="t-planes">{{ __('home.plans.title') }}</h2>
                    <p class="sec-text">{{ __('home.plans.text') }}</p>
                </div>
                <div class="filters" role="group" aria-label="{{ $tr('home.plans.filter_label', 'Filtrar por duración') }}">
                    <button type="button" class="filter is-active" :class="{ 'is-active': f === 'all' }" @click="f = 'all'" :aria-pressed="(f === 'all').toString()" aria-pressed="true">{{ __('home.plans.filters.all') }}</button>
                    @foreach (['4h', '1dia', '2-3dias', '4mas'] as $fb)
                        <button type="button" class="filter" :class="{ 'is-active': f === '{{ $fb }}' }" @click="f = '{{ $fb }}'" :aria-pressed="(f === '{{ $fb }}').toString()" aria-pressed="false">{{ __('home.plans.filters.'.$fb) }}</button>
                    @endforeach
                </div>
            </div>
            @if ($sampleTag('planes'))<p class="sample-tag sample-tag--dark">{{ $sampleNote }}</p>@endif
            <div class="grid-4 grid-4--snap" tabindex="0" role="region" aria-label="{{ __('home.plans.title') }}">
                @foreach ($plans as $p)
                    <article class="plan" x-show="f === 'all' || f === '{{ $p['bucket'] }}'">
                        <div class="plan__media">
                            @if (! empty($p['image']))<x-ui.picture :src="$p['image']" alt="" sizes="(min-width: 1024px) 24vw, (min-width: 640px) 46vw, 100vw" img-class="" />@endif
                            @if ($showSamples && ! empty($p['sample']))<span class="sample-chip">{{ $sampleLabel }}</span>@endif
                            <span class="badge badge--{{ $planTone[$p['bucket']] ?? 'accent' }}">{{ $p['bucket_label'] }}</span>
                        </div>
                        <div class="plan__body">
                            <p class="plan__kicker">{{ $p['bucket_kicker'] }}</p>
                            <h3 class="plan__title">{{ Placeholder::title($p['title']) }}</h3>
                            <div class="plan__foot">
                                @php $planHasPrice = ! empty($p['price_pen_cents']) || ! empty($p['price']); @endphp
                                @if ($planHasPrice)
                                <p class="plan__price">{{ __('home.plans.from') }}
                                    <strong>
                                        @if (! empty($p['price_pen_cents']) && ! empty($p['price_usd_cents']))
                                            <x-ui.money :pen-cents="$p['price_pen_cents']" :usd-cents="$p['price_usd_cents']" />
                                        @elseif (! empty($p['price']) && ! empty($p['price_usd']))
                                            <span x-data><span x-show="$store.currency.code === 'PEN'">{{ $p['price'] }}</span><span x-show="$store.currency.code === 'USD'" x-cloak>{{ $p['price_usd'] }}</span><noscript>{{ $p['price'] }}</noscript></span>
                                        @elseif (! empty($p['price']))
                                            {{ $p['price'] }}
                                        @endif
                                    </strong>
                                </p>
                                @else
                                <a class="price-ask" href="{{ WhatsApp::url(__('site.whatsapp.price_text', ['tour' => Placeholder::title($p['title'])])) }}" target="_blank" rel="noopener"><x-glyph name="whatsapp" :size="16" />{{ __('site.whatsapp.price_ask') }}</a>
                                @endif
                                <a class="plan__link" href="{{ $p['url'] ?? route('tours.index') }}" aria-label="{{ __('home.plans.view_plans') }}: {{ Placeholder::title($p['title']) }}">{{ __('home.plans.view_plans') }}<x-glyph name="arrow-right" :size="13" /></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= Razones + testimonios ================= --}}
    <section class="ip-sec ip-sec--alt ip-sec--reasons" id="por-que-elegirnos" aria-labelledby="t-razones">
        <div class="container">
            <div class="sec-head sec-head--center">
                <div class="sec-head__main">
                    <p class="ip-eyebrow">{{ __('home.reasons.eyebrow') }}</p>
                    <h2 class="sec-title" id="t-razones">{{ __('home.reasons.title') }}</h2>
                </div>
            </div>
            <ul class="grid-4 reasons">
                @php $rIcons = [['flag', 'accent-soft'], ['headset', 'forest-soft'], ['shield-check', 'teal-soft'], ['clock', 'accent-soft']]; @endphp
                @foreach (__('home.reasons.items') as $i => $r)
                    <li class="reason">
                        <span class="icon-tile icon-tile--lg icon-tile--{{ $rIcons[$i][1] }}"><x-glyph :name="$rIcons[$i][0]" :size="22" /></span>
                        <h3 class="reason__title">{{ $r['title'] }}</h3>
                        <p class="reason__text">{{ $r['text'] }}</p>
                    </li>
                @endforeach
            </ul>

            @php $testimonials = Placeholder::items(__('home.testimonials.items')); $seals = Placeholder::items(__('home.testimonials.seals')); @endphp
            @if ($testimonials)
            <div class="sec-head sec-head--center sec-head--gap">
                <div class="sec-head__main">
                    <p class="ip-eyebrow">{{ __('home.testimonials.eyebrow') }}</p>
                    <h2 class="sec-title">{{ __('home.testimonials.title') }}</h2>
                </div>
            </div>
            {{-- Testimonios y sellos: MARCADORES visibles ([Nombre del viajero], [4,9]):
                 no hay módulo de reseñas, y una reseña inventada es un defecto, no un pendiente. --}}
            <ul class="grid-3 testimonials">
                @php $avTone = ['forest', 'gold', 'teal']; @endphp
                @foreach ($testimonials as $i => $ti)
                    <li class="testi">
                        <p class="testi__quote" aria-hidden="true">&ldquo;</p>
                        <p class="rating__stars testi__stars" aria-hidden="true">@for ($k = 0; $k < 5; $k++)<x-glyph name="star" :size="12" />@endfor</p>
                        <blockquote class="testi__text">{{ $ti['text'] }}</blockquote>
                        <div class="testi__who">
                            <span class="avatar avatar--{{ $avTone[$i] ?? 'forest' }}" aria-hidden="true">{{ $ti['initial'] }}</span>
                            <span><strong>{{ $ti['name'] }}</strong><span class="testi__meta">{{ $ti['meta'] }}</span></span>
                        </div>
                    </li>
                @endforeach
            </ul>
            @endif
            @if ($seals)
            <ul class="seals">
                @foreach ($seals as $s)
                    <li class="seal"><span class="rating__stars" aria-hidden="true">@for ($k = 0; $k < 5; $k++)<x-glyph name="star" :size="11" />@endfor</span>{{ $s['text'] }}</li>
                @endforeach
            </ul>
            @endif
        </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="ip-sec ip-sec--faq" id="faq" aria-labelledby="t-faq">
        <div class="container faq">
            <div class="faq__side">
                <p class="ip-eyebrow">{{ __('home.faq.eyebrow') }}</p>
                <h2 class="sec-title" id="t-faq">{{ __('home.faq.title') }}</h2>
                <p class="sec-text">{{ __('home.faq.text') }}</p>
                <div class="contact-card">
                    <div class="contact-card__head">
                        <span class="contact-card__avatar" aria-hidden="true">{{ config('brand.manager.initials') }}</span>
                        <span><strong>{{ config('brand.manager.name') }}</strong><span class="contact-card__role">{{ config('brand.manager.role') }}</span></span>
                    </div>
                    <p><x-glyph name="whatsapp" :size="16" /><a href="{{ $waUrl }}" target="_blank" rel="noopener">{{ $contactPhone }}</a></p>
                    <p><x-glyph name="mail" :size="16" /><a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
                    <a class="btn btn--wa btn--block" href="{{ $waUrl }}" target="_blank" rel="noopener"><x-glyph name="whatsapp" :size="18" />{{ __('home.faq.contact_cta') }}</a>
                </div>
            </div>
            {{-- Acordeón: Alpine.data('accordion') en app.js (uno abierto a la vez, flechas/Inicio/Fin). --}}
            @php $faqItems = Placeholder::items(trans('home.faq.items', ['phone' => $contactPhone])); @endphp
            @if ($faqItems)
            <div class="accordion" x-data="accordion(0)">
                @foreach ($faqItems as $i => $fq)
                    <div class="acc {{ $i === 0 ? 'is-open' : '' }}" :class="{ 'is-open': open === {{ $i }} }">
                        <h3 class="acc__h">
                            <button type="button" class="acc__btn" id="faq-b{{ $i }}" @click="toggle({{ $i }})" @keydown="key($event, {{ $i }})" :aria-expanded="(open === {{ $i }}).toString()" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faq-p{{ $i }}">
                                <span>{{ $fq['q'] }}</span>
                                <span class="acc__icon" aria-hidden="true"><x-glyph name="plus" :size="14" class="acc__plus" /><x-glyph name="minus" :size="14" class="acc__minus" /></span>
                            </button>
                        </h3>
                        <div class="acc__panel" id="faq-p{{ $i }}" role="region" aria-labelledby="faq-b{{ $i }}" x-show="open === {{ $i }}" @if ($i !== 0) style="display: none" @endif>
                            <p>{{ $fq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </section>

    {{-- ================= Instagram ================= --}}
    @php $ig = array_slice($fx['instagram'], 0, 5); @endphp
    <section class="ig" aria-label="Instagram">
        @foreach ($ig as $k => $src)
            @if ($k === 3)
                <div class="ig__cell ig__cell--text">
                    <x-glyph name="camera" :size="26" />
                    <p class="ig__handle">{{ config('brand.contact.instagram_handle') }}</p>
                    <p class="ig__text">{{ __('home.instagram.text') }}</p>
                    @if (config('brand.social.instagram'))
                        <a class="btn btn--accent btn--sm" href="{{ config('brand.social.instagram') }}" target="_blank" rel="noopener">{{ __('home.instagram.cta') }}</a>
                    @else
                        <span class="btn btn--accent btn--sm btn--off">{{ __('home.instagram.cta') }}</span>
                    @endif
                </div>
            @endif
            <div class="ig__cell"><x-ui.picture :src="asset($src)" :alt="__('home.instagram.alts')[$k] ?? ''" sizes="(min-width: 1024px) 17vw, 50vw" img-class="" /></div>
        @endforeach
    </section>

    {{-- ================= Newsletter =================
         Solo UI: no hay backend de correo todavía. El mensaje al enviar lo dice
         con claridad (no finge una suscripción). --}}
    <section class="news" aria-labelledby="t-news">
        <div class="container news__inner">
            <div>
                <h2 class="news__title" id="t-news">{{ __('home.newsletter.title') }}</h2>
                <p class="news__text">{{ __('home.newsletter.text') }}</p>
            </div>
            <form class="news__form" novalidate x-data="{ msg: '' }" @submit.prevent="msg = ($refs.email.checkValidity() && $refs.email.value) ? @js($tr('home.newsletter.inactive', 'Gracias. La suscripción aún no está activa en esta versión del sitio.')) : @js($tr('home.newsletter.invalid', 'Escribe un correo válido.'))">
                <label class="sr-only" for="news-email">{{ __('home.newsletter.placeholder') }}</label>
                <x-glyph name="mail" :size="18" />
                <input id="news-email" x-ref="email" type="email" name="email" placeholder="{{ __('home.newsletter.placeholder') }}" autocomplete="email" required>
                <button type="submit" class="btn btn--dark">{{ __('home.newsletter.submit') }}</button>
                <p class="news__status" role="status" x-text="msg"></p>
            </form>
        </div>
    </section>

</x-layout>
