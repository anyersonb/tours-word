@php
    use App\Models\Setting;

    /**
     * "CERTIFICADOS POR MINCETUR" es una afirmación regulatoria: solo se
     * publica si existe el número de registro (RNAVT/MINCETUR) en Setting.
     * Sin ese dato, el componente no imprime nada — nunca texto fijo.
     */
    $rnavt = Setting::get('rnavt_number');
@endphp
@if(filled($rnavt))
    <x-ui.eyebrow variant="amber" {{ $attributes }}>
        Certificados por MINCETUR · RNAVT {{ $rnavt }}
    </x-ui.eyebrow>
@endif
