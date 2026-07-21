{{-- Operator Billing / Paywall (Phase 7b · SG2) — the org's REAL subscription status (derived) + the tier
     catalog (placeholder prices, marked). No charge here; subscribing continues to Checkout, whose pay
     action is the account-gated Stripe seam. --}}
<div>
    <x-operator.shell active="billing" title="Billing" :subtitle="$status['label']" :viewer="$viewer">

        @if (session('billing_notice'))
            <div class="vy-flash vy-fade" role="status">{{ session('billing_notice') }}</div>
        @endif

        {{-- REAL, DERIVED status --}}
        <div class="vy-card vy-fade" style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
            <span class="vy-bill-badge vy-bill-{{ $status['active'] ? 'ok' : ($status['lapsed'] ? 'warn' : 'trial') }}">
                <span class="vy-status-dot"></span>{{ $status['label'] }}
            </span>
            <div style="flex:1">
                @if ($status['active'])
                    <div class="vy-bill-line">You're subscribed{{ $status['plan_name'] ? ' to '.$status['plan_name'] : '' }}.</div>
                @elseif ($status['on_trial'])
                    <div class="vy-bill-line"><strong>{{ $status['days'] }} {{ $status['days'] === 1 ? 'day' : 'days' }}</strong> left in your free trial — full access. Choose a plan to keep going after it ends.</div>
                @elseif ($status['trial_expired'] || $status['lapsed'])
                    <div class="vy-bill-line">Your trial has ended. Choose a plan below to reactivate full access.</div>
                @else
                    <div class="vy-bill-line">Choose a plan to subscribe.</div>
                @endif
            </div>
        </div>

        {{-- THE TIERS --}}
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

        <p class="vy-bill-disclaimer vy-fade">Prices shown are placeholders while we finalise pricing. Vyrobel isn't collecting payments yet — live billing is coming soon.</p>

        @if (app()->environment('local') && ($status['on_trial'] || $status['active']))
            {{-- DEV/TEST ONLY — expire the trial to see the access gate. Not a real action; hidden in production. --}}
            <form method="POST" action="{{ route('app.billing.dev-expire') }}" class="vy-fade" style="margin-top:8px">
                @csrf
                <button type="submit" class="vy-btn vy-btn--ghost" style="font-size:11px">Test only · expire trial</button>
            </form>
        @endif
    </x-operator.shell>
</div>
