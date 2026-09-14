@props([
    'paginator', // Illuminate\Contracts\Pagination\LengthAwarePaginator
])
{{--
    Paginación de catálogo (lote 3), nueva en el sistema de diseño. Prev/
    siguiente + "Página X de Y" en vez de números de página: sin mockup que
    replicar, se prioriza que funcione en los 3 breakpoints con el mínimo de
    elementos. Oculta entera cuando todo cabe en una sola página
    ($paginator->hasPages() ya contempla eso).
--}}
@if($paginator->hasPages())
    <nav aria-label="{{ __('site.ui.pagination.nav_label') }}" class="mt-8 flex items-center justify-between gap-4">
        <div>
            @if($paginator->onFirstPage())
                <span class="inline-flex cursor-not-allowed items-center rounded-full border border-line px-4 py-2 text-sm text-text-muted opacity-50">
                    {{ __('site.ui.pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center rounded-full border border-line px-4 py-2 text-sm text-text-2 hover:border-action hover:text-action focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
                    {{ __('site.ui.pagination.previous') }}
                </a>
            @endif
        </div>

        <p class="text-sm text-text-muted" role="status">
            {{ __('site.ui.pagination.page_of', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
        </p>

        <div>
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center rounded-full border border-line px-4 py-2 text-sm text-text-2 hover:border-action hover:text-action focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action">
                    {{ __('site.ui.pagination.next') }}
                </a>
            @else
                <span class="inline-flex cursor-not-allowed items-center rounded-full border border-line px-4 py-2 text-sm text-text-muted opacity-50">
                    {{ __('site.ui.pagination.next') }}
                </span>
            @endif
        </div>
    </nav>
@endif
