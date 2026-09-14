@props([
    // Mismo shape que ya consume x-ui.faq-item / site.contacto.faq.items:
    // [['question' => ..., 'answer' => ...], ...].
    'items' => [],
])
@php
    /**
     * Fix 4 (auditoria SEO, lote SEO): FAQPage en Contacto, sobre las 5
     * preguntas/respuestas reales que ya existen en site.contacto.faq.items
     * (ES/EN) -- nunca contenido inventado.
     *
     * Mismo escapado seguro que x-seo.breadcrumb-jsonld (ver ese componente
     * para el porque de las banderas JSON_HEX_*): las respuestas del FAQ
     * son texto libre de un archivo de idioma, no del CMS, pero el mismo
     * principio aplica -- cualquier futuro FAQ editable desde Filament debe
     * pasar por este mismo componente sin cambiar nada.
     */
    $entities = collect($items)->map(fn (array $item) => [
        '@type' => 'Question',
        'name' => $item['question'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['answer'],
        ],
    ])->all();

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $entities,
    ];
@endphp
@if(count($entities) > 0)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>
@endif
