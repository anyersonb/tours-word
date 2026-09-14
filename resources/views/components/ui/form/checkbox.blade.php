@props([
    'name',
    'label',
    'required' => false,
    'error' => null,
])
@php $id = 'field-'.$name; @endphp
<div class="flex flex-col gap-1.5">
    <div class="flex items-start gap-2.5">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            @if($required) required aria-required="true" @endif
            @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{-- Con error, el borde tambien se marca: los otros cuatro
                 campos del formulario pasaban a --danger y solo la casilla
                 seguia con el borde neutro, asi que el unico indicio era el
                 texto de abajo. Con appearance:auto el navegador dibuja su
                 propio control y NO pinta el borde del autor (medido: el
                 borde computado seguia en verde con border-danger puesto),
                 asi que la marca visible es un anillo. --}}
            {{ $attributes->class([
                'mt-0.5 h-4 w-4 shrink-0 rounded text-action focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action',
                'border-danger ring-2 ring-danger ring-offset-1' => $error,
                'border-line' => ! $error,
            ]) }}
        >
        <label for="{{ $id }}" class="text-sm text-text-2">
            {{ $slot->isEmpty() ? $label : $slot }}
        </label>
    </div>
    @if($error)
        <p id="{{ $id }}-error" class="pl-6.5 text-sm text-danger">{{ $error }}</p>
    @endif
</div>
