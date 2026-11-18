@props(['href' => '#'])
<a {{ $attributes->class(['link-more']) }} href="{{ $href }}">{{ $slot }}<x-glyph name="arrow-right" :size="15" /></a>
