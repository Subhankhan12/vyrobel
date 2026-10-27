{{-- "Boost your score": the real protections still off, each with the points it is worth and a way to its real control
     (never a points economy — the points are only each setting's share of the score, App\Support\Security\SecurityScore). --}}
@props(['score'])
@php $left = array_values(array_filter($score['protections'], fn (array $p): bool => ! $p['done'])); @endphp
<section class="vy-sx-card vy-sx-in" id="sx-boost" aria-labelledby="vy-sx-boost-t">
    <h2 class="vy-sx-card-title" id="vy-sx-boost-t">{{ __('components/security/boost.heading') }}</h2>
    <p class="vy-sx-card-sub">{{ __('components/security/boost.protections_on', ['on' => $score['on'], 'total' => $score['total']]) }}</p>
    @if ($left !== [])
        <div class="vy-sx-boost">
            @foreach ($left as $p)
                <div class="vy-sx-boost-row" data-protection="{{ $p['key'] }}">
                    <span class="vy-sx-ico is-round"><x-security.icon name="plus" /></span>
                    <span class="vy-sx-boost-text"><span class="vy-sx-boost-label">{{ $p['label'] }}</span><span class="vy-sx-boost-desc">{{ $p['desc'] }}</span></span>
                    <span class="vy-sx-boost-act">
                        <span class="vy-sx-pts">{{ __('components/security/boost.points', ['points' => $p['pts']]) }}</span>
                        <a class="vy-sx-btn vy-sx-btn--grad vy-sx-btn--sm" href="{{ $p['target'] }}" aria-label="{{ __('components/security/boost.button_aria', ['button' => $p['button'], 'action' => $p['action']]) }}">{{ $p['button'] }}</a>
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <div class="vy-sx-done"><span class="vy-sx-ico is-round"><x-security.icon name="check" /></span>{{ __('components/security/boost.all_on') }}</div>
    @endif
</section>
