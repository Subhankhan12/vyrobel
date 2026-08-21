{{-- A mega-panel row: icon tile + title + description — the design's data-megarow, faithfully. The icon
     strokes bind currentColor; the tile colour is a token (see the shell CSS — the design's #B4400F stroke
     is NOT adopted, D6: the app's --vy-accent-text serves the icon-on-tint role). --}}
@props(['key', 'title', 'desc', 'icon'])
<x-marketing.nav-link :key="$key" class="vy-mk-megarow">
    <span class="vy-mk-megaicon" aria-hidden="true"><x-marketing.icon :name="$icon" /></span>
    <span class="vy-mk-megatext"><span>{!! $title !!}</span><span>{!! $desc !!}</span></span>
</x-marketing.nav-link>
