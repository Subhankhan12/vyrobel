{{-- The Security pages' header: the score ring, its tier and one line of advice, then four tiles. Every figure comes from
     App\Support\Security\SecurityScore (derived from real settings, #39) or a real count the page passes in.
     $tiles: list of ['icon', 'n', 'label', 'href']. --}}
@props(['score', 'tiles' => []])
@php
    $circ = 326.726;
    $ringOffset = round($circ * (1 - min(100, max(0, (int) $score['num'])) / 100), 3);
@endphp
<section class="vy-sx-card vy-sx-hero vy-sx-in" aria-labelledby="vy-sx-hero-t">
    <div class="vy-sx-hero-id">
        <div class="vy-sx-ring" role="img" aria-label="{{ __('components/security/hero.score_aria', ['score' => $score['num']]) }}">
            <svg width="112" height="112" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
                <defs><linearGradient id="vySxRing" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#F0A57A"/><stop offset="1" stop-color="#C2410C"/></linearGradient></defs>
                <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(120,70,40,.12)" stroke-width="10"/>
                <circle cx="60" cy="60" r="52" fill="none" stroke="url(#vySxRing)" stroke-width="10" stroke-linecap="round" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $ringOffset }}" transform="rotate(-90 60 60)"/>
            </svg>
            <div class="vy-sx-ring-mid" aria-hidden="true"><span class="vy-sx-ring-n">{{ $score['num'] }}</span><span class="vy-sx-ring-of">{{ __('components/security/hero.of_100') }}</span></div>
        </div>
        <div>
            <div class="vy-sx-tier-chip"><span class="vy-sx-tier-dot" aria-hidden="true"></span><span class="vy-sx-tier">{{ $score['tier'] }}</span></div>
            <h2 class="vy-sx-hero-title" id="vy-sx-hero-t">{{ __('components/security/hero.heading') }}</h2>
            <p class="vy-sx-hero-cap">{{ $score['caption'] }}</p>
        </div>
    </div>
    <div class="vy-sx-tiles">
        @foreach ($tiles as $tile)
            <a class="vy-sx-tile" href="{{ $tile['href'] }}"><span class="vy-sx-ico"><x-security.icon :name="$tile['icon']" /></span><span class="vy-sx-tile-n">{{ $tile['n'] }}</span><span class="vy-sx-tile-l">{{ $tile['label'] }}</span></a>
        @endforeach
    </div>
</section>
