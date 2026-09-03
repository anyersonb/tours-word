@props([
    'name',
    'label',
    'rows' => 4,
    'placeholder' => null,
    'required' => false,
    'error' => null,
])
@php $id = 'field-'.$name; @endphp
<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="text-sm font-medium text-ink">
        {{ $label }} @if($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required aria-required="true" @endif
        @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class([
            'rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-text-muted focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-action',
            'border-danger' => $error,
            'border-line' => ! $error,
        ]) }}
    >{{ $slot }}</textarea>
    @if($error)
        <p id="{{ $id }}-error" class="text-sm text-danger">{{ $error }}</p>
    @endif
</div>
