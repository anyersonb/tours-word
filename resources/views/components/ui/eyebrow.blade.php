@props([
    'variant' => 'brand', // brand | amber — pastilla suave con punto, uso en hero/eyebrows
])
@php
    $variants = [
        'brand' => ['dot' => 'bg-action', 'text' => 'text-brand-text', 'bg' => 'bg-brand-50'],
        'amber' => ['dot' => 'bg-amber-text', 'text' => 'text-amber-text', 'bg' => 'bg-surface-2'],
    ];
    $v = $variants[$variant] ?? $variants['brand'];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold uppercase tracking-wide', $v['bg'], $v['text']]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $v['dot'] }}" aria-hidden="true"></span>
    {{ $slot }}
</span>
