@props([
    'name',
    'label',
    'options' => [], // ['value' => 'label']
    'placeholder' => null,
    'required' => false,
    'error' => null,
    'value' => null, // R2: preserva la opción elegida tras un error de validación (pasar old($name))
])
@php $id = 'field-'.$name; @endphp
<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="text-sm font-medium text-ink">
        {{ $label }} @if($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required aria-required="true" @endif
        @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class([
            'rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-ink focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-action',
            'border-danger' => $error,
            'border-line' => ! $error,
        ]) }}
    >
        @if($placeholder)
            <option value="" disabled @selected(blank($value))>{{ $placeholder }}</option>
        @endif
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if($error)
        <p id="{{ $id }}-error" class="text-sm text-danger">{{ $error }}</p>
    @endif
</div>
