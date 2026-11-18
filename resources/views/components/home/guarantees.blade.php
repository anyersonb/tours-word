@php
    $icons = ['flag' => 'accent-soft', 'shield-check' => 'forest-soft', 'award' => 'teal-soft', 'headset' => 'clay-soft', 'calendar' => 'accent-soft'];
    $keys = array_keys($icons);
@endphp
<div class="container">
    <ul class="guarantees">
        @foreach (__('home.guarantees') as $i => $g)
            <li class="guarantee">
                <span class="icon-tile icon-tile--{{ $icons[$keys[$i]] }}"><x-glyph :name="$keys[$i]" :size="20" /></span>
                <span class="guarantee__body"><strong class="guarantee__title">{{ $g['title'] }}</strong><span class="guarantee__text">{{ $g['text'] }}</span></span>
            </li>
        @endforeach
    </ul>
</div>
