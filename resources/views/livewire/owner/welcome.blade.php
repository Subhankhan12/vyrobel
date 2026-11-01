{{-- Owner WELCOME / onboarding (Wave B · GATE 6) — the honest thin flow: real units, the REAL management-fee rate,
     a hand-off to the certified Payout Method (NO IBAN capture, NO fake micro-deposit), real next steps. Nothing
     fabricated (#39). --}}
<x-owner.shell active="welcome" :title="__('livewire/owner/welcome.title')" :subtitle="__('livewire/owner/welcome.subtitle')" :viewer="$viewer">

    <div style="max-width:720px;margin:0 auto">
        <section class="vy-card vy-fade" style="padding:26px;text-align:center">
            <div style="width:52px;height:52px;border-radius:14px;background:var(--vy-grad);display:grid;place-items:center;margin:0 auto 14px;color:#fff">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>
            </div>
            <h1 style="font-family:var(--vy-display);font-weight:600;font-size:22px;margin:0">{{ $firstName !== '' ? __('livewire/owner/welcome.heading_named', ['name' => $firstName]) : __('livewire/owner/welcome.heading') }}</h1>
            <p class="vy-line-sub" style="color:var(--vy-ink);font-size:13.5px;margin-top:8px">{{ __('livewire/owner/welcome.lede') }}</p>
        </section>

        {{-- Step 1 — your units (real) --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.04s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:rgba(217,102,46,.14);color:var(--vy-ink);display:grid;place-items:center;font-weight:700;font-size:13px">1</span>
                <h2 class="vy-card-title" style="margin:0">{{ __('livewire/owner/welcome.units_title') }}</h2>
            </div>
            @if ($unitCount > 0)
                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0 0 8px">{{ trans_choice('livewire/owner/welcome.units_on_account', $unitCount, ['count' => $unitCount]) }} <strong style="color:var(--vy-ink)">{{ implode(', ', $units) }}</strong></p>
                <a href="{{ route('owner.units') }}" style="color:var(--vy-ink);font-size:12.5px;font-weight:600;text-decoration:underline;text-underline-offset:2px">{{ __('livewire/owner/welcome.open_units') }}</a>
            @else
                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0">{{ __('livewire/owner/welcome.no_units') }} <a href="{{ route('owner.add-unit') }}" style="color:var(--vy-ink);font-weight:600;text-decoration:underline;text-underline-offset:2px">{{ __('livewire/owner/welcome.request_unit') }}</a></p>
            @endif
        </section>

        {{-- Step 2 — your management fee (REAL rate, never the wireframe's hardcoded 15%) --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.08s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:rgba(217,102,46,.14);color:var(--vy-ink);display:grid;place-items:center;font-weight:700;font-size:13px">2</span>
                <h2 class="vy-card-title" style="margin:0">{{ __('livewire/owner/welcome.paid_title') }}</h2>
            </div>
            @if ($commission !== null)
                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0">{!! __('livewire/owner/welcome.fee_line', ['pct' => e((string) $commission)]) !!}</p>
            @else
                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0">{{ __('livewire/owner/welcome.fee_unknown') }}</p>
            @endif
        </section>

        {{-- Step 3 — payout account hand-off (NO IBAN capture here — the certified page owns it) --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.12s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:{{ $hasPayout ? 'rgba(34,120,60,.14)' : 'rgba(224,146,30,.16)' }};color:{{ $hasPayout ? '#186040' : 'var(--vy-amber-text)' }};display:grid;place-items:center;font-weight:700;font-size:13px">3</span>
                <h2 class="vy-card-title" style="margin:0">{{ $hasPayout ? __('livewire/owner/welcome.payout_title') : __('livewire/owner/welcome.payout_title_add') }}</h2>
            </div>
            @if ($hasPayout)
                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0 0 8px">{{ __('livewire/owner/welcome.payout_on_file') }}</p>
            @else
                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0 0 8px">{{ __('livewire/owner/welcome.payout_add') }}</p>
            @endif
            <a href="{{ route('owner.payout-method') }}" style="color:var(--vy-ink);font-size:12.5px;font-weight:600;text-decoration:underline;text-underline-offset:2px">{{ $hasPayout ? __('livewire/owner/welcome.payout_review') : __('livewire/owner/welcome.payout_add_link') }}</a>
        </section>

        {{-- Step 4 — notifications --}}
        <section class="vy-card vy-fade" style="padding:20px;margin-top:14px;animation-delay:.16s">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="width:26px;height:26px;border-radius:8px;background:rgba(217,102,46,.14);color:var(--vy-ink);display:grid;place-items:center;font-weight:700;font-size:13px">4</span>
                <h2 class="vy-card-title" style="margin:0">{{ __('livewire/owner/welcome.notify_title') }}</h2>
            </div>
            <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0 0 8px">{{ __('livewire/owner/welcome.notify_body') }}</p>
            <a href="{{ route('owner.notification-preferences') }}" style="color:var(--vy-ink);font-size:12.5px;font-weight:600;text-decoration:underline;text-underline-offset:2px">{{ __('livewire/owner/welcome.notify_link') }}</a>
        </section>

        {{-- Done --}}
        <section class="vy-card vy-fade" style="padding:22px;margin-top:14px;text-align:center;animation-delay:.2s">
            <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0 0 12px">{{ __('livewire/owner/welcome.done') }}</p>
            <a href="{{ route('owner.dashboard') }}" class="vy-btn vy-btn--accent" style="display:inline-flex;padding:10px 22px;text-decoration:none">{{ __('livewire/owner/welcome.go_dashboard') }}</a>
        </section>
    </div>

</x-owner.shell>
