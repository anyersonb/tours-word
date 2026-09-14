@props([
    // Mismo shape que ya consume x-ui.breadcrumbs: [['label' => ..., 'href' => ...|omitido], ...].
    // El ultimo item (la pagina actual) normalmente no trae "href" -- schema.org
    // no lo exige para el ultimo ListItem.
    'items' => [],
])
@php
    /**
     * Fix 4 (auditoria SEO, lote SEO): BreadcrumbList en las fichas que ya
     * tienen migas de pan reales (destinations/show, experiences/show).
     *
     * Escapado seguro (obligatorio, ver el reporte del lote -- en otro
     * proyecto de la casa un JSON-LD mal escapado abrio un XSS): las banderas
     * JSON_HEX_TAG/JSON_HEX_APOS/JSON_HEX_QUOT/JSON_HEX_AMP convierten
     * <, >, ', ", & en escapes \uXXXX dentro del JSON, asi que un nombre de
     * destino/experiencia con "</script><script>...", comillas o un
     * <img onerror=...> nunca puede cerrar el <script> ni inyectar HTML --
     * ver tests/Feature/StructuredDataTest.php. Por eso se imprime con
     * {!! !!} (json_encode ya deja el contenido seguro para un bloque
     * <script>; {{ }} lo volveria a escapar con entidades HTML y rompería
     * el JSON).
     */
    $listItems = collect($items)->values()->map(function (array $item, int $index) {
        $entry = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
        ];

        if (filled($item['href'] ?? null)) {
            $entry['item'] = $item['href'];
        }

        return $entry;
    })->all();

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $listItems,
    ];
@endphp
@if(count($listItems) > 0)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>
@endif
