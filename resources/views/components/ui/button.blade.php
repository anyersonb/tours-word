@props([
    'variant' => 'primary', // primary | secondary | ghost | link
    'size' => 'md', // sm | md
    'href' => null,
    'icon' => null, // slot HTML opcional a la izquierda
    'disabled' => false,
    'type' => 'button', // button | submit | reset — ignorado si hay href (se renderiza <a>)
])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action disabled:cursor-not-allowed disabled:opacity-50';

    $sizes = [
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-5 py-2.5 text-sm',
    ];

    $variants = [
        'primary' => 'bg-action text-on-action hover:bg-action-hover',
        'secondary' => 'border border-line text-text-2 hover:border-action hover:text-action bg-surface',
        'ghost' => 'text-action hover:bg-brand-50',
        'link' => 'text-action underline-offset-4 hover:underline p-0 rounded-none',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
    $tag = $href && !$disabled ? 'a' : 'button';
    $validTypes = ['button', 'submit', 'reset'];
    $buttonType = in_array($type, $validTypes, true) ? $type : 'button';
@endphp
<{{ $tag }}
    @if($tag === 'a') href="{{ $href }}" @else type="{{ $buttonType }}" @endif
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->class([$classes]) }}
>
    @if($icon){!! $icon !!}@endif
    {{ $slot }}
</{{ $tag }}>
