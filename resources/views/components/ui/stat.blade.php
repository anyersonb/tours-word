@props([
    'value' => null,
    'label' => null,
])
{{--
    Una estadística individual. Si no hay valor real, NO SE RENDERIZA —
    regla dura del proyecto: cero cifras inventadas. El valor tiene que
    llegar ya resuelto desde App\Models\Setting, nunca escrito acá.
--}}
@if(filled($value))
    <div {{ $attributes->class(['flex flex-col items-center gap-1 text-center']) }}>
        <span class="font-display text-2xl font-semibold text-action sm:text-3xl">{{ $value }}</span>
        <span class="text-xs text-text-2 sm:text-sm">{{ $label }}</span>
    </div>
@endif
