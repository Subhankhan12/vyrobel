{{-- The tier catalog (Essential / Pro) — REAL plan rows + config feature splits, PLACEHOLDER prices.
     Shared by the paywall (lapsed / no plan) and the active-plan "Change plan" section. Each "Continue"
     links to the certified Checkout (the account-gated Stripe seam — no charge until go-live).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div class="vy-bill-tiers vy-fade">
    @foreach ($tiers as $tier)
        <div class="vy-bill-tier @if ($tier['code'] === 'pro') is-featured @endif">
            @if ($tier['code'] === 'pro')<span class="vy-bill-tag">Most popular</span>@endif
            <h3 class="vy-bill-name">{{ $tier['name'] }}</h3>
            <p class="vy-bill-blurb">{{ $tier['blurb'] }}</p>
            <div class="vy-bill-price">{{ $tier['monthly'] }}<span class="vy-bill-per">/mo</span></div>
            <div class="vy-bill-yearly">or {{ $tier['yearly'] }}/yr ({{ $tier['yearly_monthly_equiv'] }}/mo billed yearly)</div>
            <ul class="vy-bill-feats">
                @foreach ($tier['features'] as $f)
                    <li class="@if (! $f['on']) is-off @endif">
                        @if ($f['on'])
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        @endif
                        {{ $f['label'] }}@if ($f['on'] && $f['note']) <span class="vy-bill-note">· {{ $f['note'] }}</span>@endif
                    </li>
                @endforeach
            </ul>
            <a class="vy-btn @if ($tier['code'] === 'pro') vy-btn--accent @else vy-btn--ghost @endif" href="{{ route('app.billing.checkout', ['plan' => $tier['code'], 'interval' => 'monthly']) }}">Continue with {{ $tier['name'] }}</a>
        </div>
    @endforeach
</div>
