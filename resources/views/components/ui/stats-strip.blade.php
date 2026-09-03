@php
    use App\Models\Setting;

    /**
     * Franja de estadísticas (A7/A8). Los mockups publican "10+ años",
     * "+8,500 viajeros", etc. — la clienta es una agencia nueva y ninguna
     * cifra tiene respaldo. Cada valor sale de Setting; si no hay dato, ese
     * stat individual no se pinta (ver x-ui.stat), y si NINGUNO tiene dato
     * la franja entera no se renderiza — nunca un placeholder publicable.
     */
    $items = [
        ['key' => 'stat_years_experience', 'label' => __('Años de experiencia'), 'suffix' => '+'],
        ['key' => 'stat_happy_travelers', 'label' => __('Viajeros felices'), 'suffix' => '+'],
        ['key' => 'stat_tours_completed', 'label' => __('Tours realizados'), 'suffix' => '+'],
        ['key' => 'stat_destinations_count', 'label' => __('Destinos'), 'suffix' => '+'],
    ];

    $resolved = collect($items)->map(function ($item) {
        $raw = Setting::get($item['key']);

        return [
            'label' => $item['label'],
            'value' => filled($raw) ? $raw.$item['suffix'] : null,
        ];
    });

    $hasAny = $resolved->contains(fn ($item) => filled($item['value']));
@endphp
@if($hasAny)
    <div {{ $attributes->class(['grid grid-cols-2 gap-6 rounded-2xl border border-line bg-surface p-6 shadow-sm sm:grid-cols-4']) }}>
        @foreach($resolved as $item)
            <x-ui.stat :value="$item['value']" :label="$item['label']" />
        @endforeach
    </div>
@endif
