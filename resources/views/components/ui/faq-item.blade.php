@props([
    'question',
])
{{--
    <details>/<summary> nativo: teclado (Enter/Espacio, foco visible) y
    lectores de pantalla funcionan sin ARIA a mano ni JS. Cada pregunta es
    independiente (varias pueden estar abiertas a la vez), como en el mockup.
--}}
<details class="group border-b border-line-soft py-4 first:pt-0 last:border-b-0">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-ink marker:content-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
        {{ $question }}
        <svg class="h-5 w-5 shrink-0 text-action transition-transform duration-200 group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 5v14M5 12h14" />
        </svg>
    </summary>
    <div class="pt-3 text-sm text-text-2">
        {{ $slot }}
    </div>
</details>
