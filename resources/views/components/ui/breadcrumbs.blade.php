@props([
    'items' => [], // [['label' => 'Inicio', 'href' => '/'], ['label' => 'Contacto']]
])
{{--
    Migas de pan (C8, nuevo en el lote 1/etapa C). El último ítem nunca lleva
    href (es la página actual, marcada con aria-current); los anteriores solo
    son <a> si traen "href" — así una miga intermedia sin ruta real (ej. una
    categoría que todavía no existe) no queda como enlace roto a "#".
--}}
<nav aria-label="Miga de pan" {{ $attributes->class(['text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach($items as $item)
            <li class="flex items-center gap-1.5">
                @if(!$loop->first)
                    <span aria-hidden="true" class="text-text-muted">/</span>
                @endif
                @if(!empty($item['href']) && !$loop->last)
                    <a href="{{ $item['href'] }}" class="text-text-2 hover:text-action">{{ $item['label'] }}</a>
                @else
                    <span class="text-ink" @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
