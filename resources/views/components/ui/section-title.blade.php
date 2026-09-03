@props([
    'as' => 'h2',
])
@php $tag = $as; @endphp
{{--
    A9 (lote 1, etapa B): slot opcional "action" para el enlace "Ver todos"
    que la Home pone junto al título de varias secciones. Sin ese slot el
    marcado y el margen quedan exactamente iguales que en la etapa A
    (verificado: /_styleguide no cambia).
--}}
<div {{ $attributes->class(['mb-6 flex flex-wrap items-end gap-4']) }}>
    <div>
        <{{ $tag }} class="font-display text-2xl font-semibold text-ink sm:text-3xl">{{ $slot }}</{{ $tag }}>
        <span class="mt-2 block h-1 w-12 rounded-full bg-action" aria-hidden="true"></span>
    </div>
    {{--
        ml-auto en vez de justify-between en el contenedor: con
        justify-between, si el flex envuelve (mobile angosto), la línea que
        queda con un solo ítem lo ancla a la izquierda (medido: x=16 igual
        que el título). ml-auto empuja el enlace a la derecha SIEMPRE,
        envuelva o no — verificado a 360px.
    --}}
    @isset($action)
        <div class="ml-auto">{{ $action }}</div>
    @endisset
</div>
