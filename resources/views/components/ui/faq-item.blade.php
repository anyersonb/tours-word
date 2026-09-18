@props([
    'question',
])
{{--
    <details>/<summary> nativo: teclado (Enter/Espacio, foco visible) y
    lectores de pantalla funcionan sin ARIA a mano ni JS. Cada pregunta es
    independiente (varias pueden estar abiertas a la vez), como en el mockup.
--}}
<details {{ $attributes->class(['group border-b border-line-soft py-4 first:pt-0 last:border-b-0']) }}>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-ink marker:content-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
        {{ $question }}
        <svg class="h-5 w-5 shrink-0 text-action transition-transform duration-200 group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 5v14M5 12h14" />
        </svg>
    </summary>
    {{--
        Defecto 1 (auditoria cliente, 2026-09-14): el slot ya llega
        escapado -- cada llamador interpola su contenido con {{ }} (texto
        plano) o ya lo saneo con App\Support\Html\RichTextSanitizer antes de
        imprimirlo con {!! !!} (HTML de confianza, ver tours/show.blade.php).
        Un {{ $slot }} aqui volvia a escapar ese resultado ya seguro
        (double-escape en el caso de texto plano con "&"/"<"/">", y el mismo
        bug del defecto 1 -- tags a la vista -- en el caso del HTML
        saneado).
    --}}
    <div class="pt-3 text-sm text-text-2">
        {!! $slot !!}
    </div>
</details>
