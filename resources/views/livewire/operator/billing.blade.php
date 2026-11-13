{{-- Operator Billing (exact-match #85) — the operator's OWN subscription (paying Vyrobel). The active-plan
     MANAGEMENT view (plan · per-unit figure · what-you-pay · billing details · pause/cancel) + the tier
     catalog / paywall for a lapsed / no-plan org.
     HONESTY: the STATE is real (status/trial/period + the REAL read-only pause/cancel window); the per-unit
     FIGURE is real (org's real active-unit count × a PLACEHOLDER per-unit price × VAT, reconciling #17); the
     CHARGE + stored CARD + PAID invoices are account-gated — NEVER fabricated (no fake "····4242"/"Paid").
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="billing" :title="__('livewire/operator/billing.page_title')" :subtitle="$status['label']" :viewer="$viewer">

        {{-- Settings sub-nav (General / Team / Billing) --}}
        <div class="vy-subnav vy-fade">
            <a href="{{ route('app.settings') }}" class="vy-subnav-tab">{{ __('livewire/operator/billing.tab_general') }}</a>
            <a href="{{ route('app.roles') }}" class="vy-subnav-tab">{{ __('livewire/operator/billing.tab_team') }}</a>
            <span class="vy-subnav-tab is-active">{{ __('livewire/operator/billing.tab_billing') }}</span>
        </div>

        @if (session('billing_notice'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ session('billing_notice') }}</div></div>
        @endif
        @if (session('billing_error'))
            <div class="vy-card vy-fade" style="border-left:3px solid #B4400F;margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ session('billing_error') }}</div></div>
        @endif

        {{-- ── CARD ACCEPTANCE (Stripe Connect) ───────────────────────────────────────────────────────
             ⚠️ THIS SCREEN IS WHAT MAKES THE DIRECT-CHARGE SWITCH USABLE. All three payment paths now
             charge on THIS organisation's own Stripe account, so until it is verified no card can be
             taken anywhere — storefront, guest portal or front desk.
             ⚠️ PROMISES NOTHING: no date, no "you'll be able to", and every destination named here is
             reachable today. Two earlier passes put false claims into error strings; this is not a third.
             ⚠️ READINESS IS STRIPE'S, read live — `details_submitted` is NOT `charges_enabled`.
             RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
        <div class="vy-card vy-fade" style="margin-bottom:14px">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <div class="vy-card-title">{{ __('livewire/operator/billing.card_acceptance') }}</div>
                    <div class="vy-recon" style="margin-top:2px">{{ __('livewire/operator/billing.card_acceptance_sub') }}</div>
                </div>
                <div>
                    @if ($connect['ready'])
                        <span class="vy-chip is-ok">{{ __('livewire/operator/billing.connect_verified') }}</span>
                    @elseif ($connect['details_submitted'])
                        {{-- ⚠️ SUBMITTED IS NOT READY. Stripe still has to enable charges, and saying "done"
                             here is how a refused card later reads as a broken product. --}}
                        <span class="vy-chip">{{ __('livewire/operator/billing.connect_submitted') }}</span>
                    @elseif ($connect['connected'])
                        <span class="vy-chip">{{ __('livewire/operator/billing.connect_started') }}</span>
                    @else
                        <span class="vy-chip">{{ __('livewire/operator/billing.connect_not_set_up') }}</span>
                    @endif
                </div>
            </div>

            @if (! $connect['ready'])
                <div class="vy-recon" style="margin-top:12px">
                    {{ __('livewire/operator/billing.connect_not_ready') }}
                </div>
            @endif

            @if (! $connect['connected'])
                {{-- ⚠️⚠️ THE COUNTRY WARNING BELONGS *BEFORE* CREATION. A Connect account's country cannot
                     be changed afterwards, and nothing in Vyrobel stops an operator holding properties in
                     more than one country — so the trap is discovered months later, while expanding. --}}
                <div class="vy-card" style="margin-top:12px;border-left:3px solid #B4400F">
                    <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ __('livewire/operator/billing.country_heading') }}</div>
                    <div class="vy-recon" style="margin-top:4px">
                        {{ __('livewire/operator/billing.country_body') }}
                        @if ($connect['country'])
                            {!! __('livewire/operator/billing.country_recorded', ['country' => e($connect['country'])]) !!}
                        @else
                            {{ __('livewire/operator/billing.country_missing') }}
                        @endif
                    </div>
                </div>
            @endif

            {{-- ⚠️⚠️ NO STATEMENT-DESCRIPTOR FIELD, DELIBERATELY. Stripe: "The customer's statement uses the
                 CONNECTED ACCOUNT's static component" for direct charges, and accounts with a Stripe-hosted
                 dashboard "can update their own statement descriptor settings". A field here would write
                 somewhere the operator can silently override — telling them they had configured something
                 they had not. Guidance instead, pointing at a destination that genuinely exists. --}}
            <div class="vy-card" style="margin-top:12px">
                <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ __('livewire/operator/billing.descriptor_heading') }}</div>
                <div class="vy-recon" style="margin-top:4px">
                    {{ __('livewire/operator/billing.descriptor_body') }}
                </div>
                <div class="vy-recon" style="margin-top:6px">
                    {{ __('livewire/operator/billing.descriptor_dispute') }}
                </div>
                @if ($connect['connected'])
                    <div style="margin-top:8px"><a href="https://dashboard.stripe.com/settings/public" target="_blank" rel="noopener" class="vy-btn vy-btn-ghost">{{ __('livewire/operator/billing.open_stripe_settings') }}</a></div>
                @endif
            </div>

            <form method="POST" action="{{ route('app.billing.connect.start') }}" style="margin-top:12px">
                @csrf
                {{-- ⚠️ A FRESH LINK IS MINTED ON EVERY SUBMIT — never rendered into the page and never
                     cached. Stripe's account links expire and are single-use, so one held on an open page
                     fails days later for someone mid-signup. An abandoned setup resumes on the SAME
                     account with a new link. --}}
                <button type="submit" class="vy-btn">
                    @if ($connect['ready'])
                        {{ __('livewire/operator/billing.connect_review') }}
                    @elseif ($connect['connected'])
                        {{ __('livewire/operator/billing.connect_continue') }}
                    @else
                        {{ __('livewire/operator/billing.connect_start') }}
                    @endif
                </button>
            </form>
        </div>

        {{-- Live read-only lifecycle state (canceling / paused) — the REAL state this page writes --}}
        @if ($lifecycle['is_canceling'])
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:14px">
                <div><div class="vy-bill-line">{!! __('livewire/operator/billing.subscription_ending', ['date' => e($lifecycle['readonly_from'])]) !!}</div></div>
                <form method="POST" action="{{ route('app.billing.resume') }}">@csrf<button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/billing.keep_my_plan') }}</button></form>
            </div>
        @elseif ($lifecycle['is_paused'])
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:14px">
                <div><div class="vy-bill-line">{!! __('livewire/operator/billing.workspace_paused', ['date' => e($lifecycle['paused_until'])]) !!}</div></div>
                <form method="POST" action="{{ route('app.billing.resume') }}">@csrf<button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/billing.resume_now') }}</button></form>
            </div>
        @endif

        @if ($status['active'] && $plan)
            {{-- ══════════ ACTIVE-SUBSCRIPTION MANAGEMENT VIEW (a real, chosen/paid plan) ══════════ --}}
            <div class="vy-dash-cols">
                <div class="vy-stack">
                    {{-- Plan card --}}
                    <section class="vy-card">
                        {{-- The design's plan header: the plan NAME as the heading with the status as a pill
                             beside it, and the price right-aligned on the same row. (This was inverted — the
                             plan name sat in the chip and the status was plain text.) --}}
                        <div class="vy-bill-head">
                            <div style="min-width:0">
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                    <span class="vy-bill-plan">{{ $plan['name'] }}</span>
                                    <span class="vy-bill-badge vy-bill-{{ $status['active'] ? 'ok' : 'trial' }}"><span class="vy-status-dot"></span>{{ $status['label'] }}</span>
                                </div>
                                {{-- ⚠️ BILLING PASS 3 — keyed by the plan's SHAPE. The per-unit breakdown is inside
                                     the per-unit branch, so an org on a FLAT tier cannot render it: the false
                                     "N units × €rate" line is unreachable rather than merely hidden (§N.16.3). --}}
                                <div class="vy-bill-line" style="margin-top:8px;color:var(--vy-muted);font-size:12.5px">
                                    @if ($billing['is_per_unit'])
                                        {{ trans_choice('livewire/operator/billing.plan_line_per_unit', $billing['units'], ['interval' => $plan['interval_label'], 'count' => $billing['units'], 'rate' => $billing['unit_rate'], 'date' => $plan['subscribed_on']]) }}
                                    @else
                                        {{ __('livewire/operator/billing.plan_line_flat', ['interval' => $plan['interval_label'], 'date' => $plan['subscribed_on']]) }}
                                    @endif
                                </div>
                            </div>
                            <div class="vy-bill-price">
                                {{-- The figure comes from Plan::chargeFor() — the same expression the CHARGE uses,
                                     so the page cannot quote one number while Stripe is sent another. --}}
                                {{-- $billing is non-null exactly when $plan is (both derive from the same
                                     subscription), so no fallback is needed — and a fallback to a key that does
                                     not exist would be a latent error rather than a safety net. --}}
                                <div><span class="vy-bill-amt">{{ $billing['gross'] }}</span><span class="vy-bill-per">{{ __('livewire/operator/billing.per_month') }}</span></div>
                                {{-- ⚠️ BILLING PASS 2b — THE PAGE STOPPED CLAIMING A TAX IT CANNOT KNOW. This printed
                                     "incl. 23% VAT" on a per-unit plan. Under Stripe Tax the rate is the CUSTOMER's
                                     (§N.16.2 — their country and VAT status are data Stripe resolves at checkout), and an
                                     EU business under reverse charge pays €0 — so a fixed 23% was false for them: a new
                                     §N.8d. The amount above is now the pre-tax SUBTOTAL, exactly what reaches Stripe, and
                                     the tax is deferred rather than guessed. --}}
                                @if ($billing['tax_note'] !== '')
                                    <div class="vy-bill-vat">{{ $billing['tax_known'] ? '' : '+ ' }}{{ $billing['tax_note'] }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- The design's next-invoice STRIP. Content is the real period end + the real
                             net/VAT split. ⚠️ The design's version also names the card it will charge
                             ("to Visa ····4242") and promises a reminder email 3 days before — neither is
                             reproduced: no card is stored (SAQ A) and no such reminder is scheduled. --}}
                        @if ($plan['period_end'])
                            <div class="vy-bill-next">
                                <span class="vy-bill-next-ico" aria-hidden="true">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>
                                </span>
                                {{-- ⚠️ 2b — this read SubscriptionPricing, a SECOND hardcoded 23% pass 3 left behind, and it
                                     asserted a VAT split on EVERY shape. It now reads the same plan-derived summary as the
                                     headline (§N.8f, one definition) and says only what that shape supports. --}}
                                <span>{!! __('livewire/operator/billing.next_invoice', ['date' => e($plan['period_end']), 'amount' => e($billing['gross'])]) !!}@if ($billing['tax_note'] !== '') · {{ $billing['tax_note'] }}@endif</span>
                            </div>
                        @else
                            <div class="vy-bill-line" style="margin-top:12px;color:var(--vy-muted);font-size:12px">{{ $billing['gross'] }}@if ($billing['tax_note'] !== '') · {{ $billing['tax_note'] }}@endif</div>
                        @endif

                        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
                            {{-- WAVE 2 · S3 — the switch changes the INTERVAL, never the tier. This linked to a
                                 hardcoded `plan=pro`, so an Essential org landed on the PRO yearly checkout (a
                                 silent upgrade); it now carries the org's own plan code. And it only appears to
                                 an org actually on MONTHLY — offering "switch to yearly" to someone already on
                                 yearly is an affordance that does nothing. --}}
                            @if ($plan['interval'] !== 'yearly')
                                {{-- The design makes this the primary action. The LINK is unchanged — it still
                                     carries the org's OWN plan code (the WAVE-2 · S3 fix), so restyling it
                                     cannot reintroduce the silent-upgrade-to-Pro bug. --}}
                                <a href="{{ route('app.billing.checkout', ['plan' => $plan['code'], 'interval' => 'yearly']) }}" class="vy-btn vy-btn--accent">{{ __('livewire/operator/billing.switch_to_yearly', ['amount' => $pricing['yearly_saving']]) }}</a>
                            @endif
                            <a href="#vy-plans" class="vy-btn vy-btn--ghost">{{ __('livewire/operator/billing.change_plan_link') }}</a>
                        </div>
                        <div class="vy-fx-note" style="margin-top:12px">{{ __('livewire/operator/billing.test_mode_note') }}</div>
                    </section>

                    {{-- Invoices (HONEST — the real trial; paid invoices are account-gated) --}}
                    <section class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom:12px">{{ __('livewire/operator/billing.invoices') }}</h2>
                        @foreach ($invoices as $inv)
                            <div class="vy-inv-row">
                                <div><div class="vy-inv-ref">{{ $inv['label'] }}</div><div class="vy-inv-sub">{{ $inv['period'] }}</div></div>
                                <div style="display:flex;align-items:center;gap:14px">
                                    <span class="vy-status vy-status--paid" style="font-size:11px">{{ $inv['status'] }}</span>
                                    <span class="vy-mono" style="font-weight:600">{{ $inv['amount'] }}</span>
                                </div>
                            </div>
                        @endforeach
                        <div class="vy-fx-note" style="margin-top:10px">{{ __('livewire/operator/billing.invoices_note') }}</div>
                    </section>

                </div>

                {{-- Right rail --}}
                <div class="vy-stack">
                    {{-- What you're paying for (REAL counts) --}}
                    <section class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom:10px">{{ __('livewire/operator/billing.paying_for') }}</h2>
                        {{-- ⚠️ The unit COUNT is shown on every shape (it is a real fact about the account), but
                             what it COSTS and the proration promise belong to a per-unit plan only. On a flat
                             tier "+€9/mo · prorated" was simply false — adding a unit changes nothing. --}}
                        @if ($billing !== null && $billing['is_per_unit'])
                            <div class="vy-mini-row"><span class="vy-mini-k">{{ __('livewire/operator/billing.units_on_rack') }}</span><span class="vy-mini-v">{{ $billing['units'] }} × {{ $billing['unit_rate'] }}</span></div>
                        @else
                            <div class="vy-mini-row"><span class="vy-mini-k">{{ __('livewire/operator/billing.units_on_rack') }}</span><span class="vy-mini-v">{{ $counts['units'] }}</span></div>
                        @endif
                        <div class="vy-mini-row"><span class="vy-mini-k">{{ __('livewire/operator/billing.team_members') }}</span><span class="vy-mini-v">{{ $counts['team_limit'] !== null ? __('livewire/operator/billing.team_of_limit', ['members' => $counts['team'], 'limit' => $counts['team_limit']]) : __('livewire/operator/billing.team_unlimited', ['members' => $counts['team']]) }}</span></div>
                        <div class="vy-mini-row"><span class="vy-mini-k">{{ __('livewire/operator/billing.channels') }}</span><span class="vy-mini-v">{{ __('livewire/operator/billing.channels_connected', ['channels' => $counts['channels']]) }}</span></div>
                        @if ($billing !== null && $billing['is_per_unit'])
                            {{-- TRUE since BILLING PASS 1: a per-unit subscription re-quantifies with Stripe
                                 proration, so the difference really is charged immediately. --}}
                            <div class="vy-mini-row"><span class="vy-mini-k">{{ __('livewire/operator/billing.adding_a_unit') }}</span><span class="vy-mini-v">{{ __('livewire/operator/billing.adding_unit_prorated', ['rate' => $billing['unit_rate']]) }}</span></div>
                        @else
                            <div class="vy-mini-row"><span class="vy-mini-k">{{ __('livewire/operator/billing.adding_a_unit') }}</span><span class="vy-mini-v">{{ __('livewire/operator/billing.adding_unit_included') }}</span></div>
                        @endif
                    </section>

                    {{-- Payment method (HONEST-GATED — no card stored) --}}
                    <section class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom:8px"><h2 class="vy-card-title">{{ __('livewire/operator/billing.payment_method') }}</h2></div>
                        <div class="vy-deferred" style="margin-top:2px">
                            <span class="vy-eyebrow">{{ __('livewire/operator/billing.no_card_on_file') }}</span>
                            {{ __('livewire/operator/billing.no_card_body') }}
                        </div>
                    </section>

                    {{-- Billing details (REAL + Edit) --}}
                    <section class="vy-card" x-data="{ edit: false }">
                        <div class="vy-detail-head" style="margin-bottom:8px">
                            <h2 class="vy-card-title">{{ __('livewire/operator/billing.billing_details') }}</h2>
                            <button type="button" class="vy-arr-link" x-on:click="edit = ! edit">{{ __('common.edit') }}</button>
                        </div>
                        <div x-show="! edit">
                            <div class="vy-bill-line" style="font-weight:600">{{ $billingDetails['company'] ?? __('livewire/operator/billing.not_set') }}</div>
                            @if ($billingDetails['country'])<div class="vy-line-sub">{{ $billingDetails['country'] }}</div>@endif
                            @if ($billingDetails['vat_id'])<div class="vy-line-sub">{{ __('livewire/operator/billing.vat_line', ['vat_id' => $billingDetails['vat_id']]) }}</div>@endif
                            @if ($billingDetails['email'])<div class="vy-line-sub">{{ $billingDetails['email'] }}</div>@endif
                        </div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('app.billing.company') }}" style="margin-top:6px">
                            @csrf
                            <label class="vy-field" style="display:block;margin-bottom:8px"><span class="vy-field-label">{{ __('livewire/operator/billing.company_legal_name') }}</span><input type="text" name="billing_company" value="{{ $billingDetails['company'] }}" class="vy-input"></label>
                            <label class="vy-field" style="display:block;margin-bottom:8px"><span class="vy-field-label">{{ __('livewire/operator/billing.vat_id') }}</span><input type="text" name="billing_vat_id" value="{{ $billingDetails['vat_id'] }}" class="vy-input"></label>
                            <label class="vy-field" style="display:block;margin-bottom:10px"><span class="vy-field-label">{{ __('livewire/operator/billing.billing_email') }}</span><input type="email" name="billing_email" value="{{ $billingDetails['email'] }}" class="vy-input"></label>
                            <button type="submit" class="vy-btn vy-btn--accent">{{ __('common.save') }}</button>
                        </form>
                    </section>

                    {{-- Cancel subscription — the design places this LAST IN THE RIGHT RAIL and subdues it,
                         rather than giving it a full-width card in the main column. The flow itself (the real
                         pause/cancel POSTs and the retention modal) is unchanged.

                         ⚠️ THE MODAL MUST LIVE OUTSIDE THE CARD — a PRE-EXISTING bug, made visible by moving
                         this card into the narrow rail and fixed here rather than carried forward.
                         `.vy-card` sets `backdrop-filter: blur(22px)`, and a backdrop-filter creates a
                         CONTAINING BLOCK for `position: fixed` descendants. So `.vy-modal-scrim` — which is
                         `position: fixed` and expects the viewport — resolved against the CARD instead:
                         measured 338×132 at (1071,840) rather than 1440×1000 at (0,0), i.e. the "centred"
                         retention modal rendered squashed inside the rail column. Confirmed identical on
                         clean HEAD with this gate's changes stashed (the culprit ancestor is the same
                         `.vy-card`), so it predates this gate — it was simply less obvious in a wide column.
                         The `x-data` scope moves to a plain wrapper with no filter, so the scrim escapes to
                         the viewport while the Alpine binding and every real POST stay exactly as they were. --}}
                    <div x-data="{ open: false }">
                        <section class="vy-card vy-bill-cancel">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                                <div style="min-width:0">
                                    <h2 class="vy-card-title" style="font-size:13px">{{ __('livewire/operator/billing.cancel_heading') }}</h2>
                                    <div class="vy-line-sub">{{ __('livewire/operator/billing.cancel_sub') }}</div>
                                </div>
                                @unless ($lifecycle['is_paused'] || $lifecycle['is_canceling'])
                                    <button type="button" class="vy-btn vy-btn--ghost" style="flex:none" x-on:click="open = true">{{ __('livewire/operator/billing.cancel_open') }}</button>
                                @endunless
                            </div>
                        </section>

                        {{-- Retention modal (Pause instead / Cancel for good / yearly offer) --}}
                        <div x-show="open" x-cloak class="vy-modal-scrim" x-on:click.self="open = false">
                            <div class="vy-modal vy-modal-card">
                                <h3 style="font-family:var(--vy-display);font-size:18px;font-weight:600;margin:0">{{ __('livewire/operator/billing.before_you_go') }}</h3>
                                <p class="vy-line-sub" style="margin:6px 0 16px">{{ trans_choice('livewire/operator/billing.retention_summary', $pricing['units'], ['plan' => $plan['name'], 'count' => $pricing['units']]) }}</p>

                                <div class="vy-retain-opt">
                                    <div><strong>{{ __('livewire/operator/billing.pause_instead', ['amount' => $pauseFree]) }}</strong><div class="vy-line-sub">{{ __('livewire/operator/billing.pause_body') }}</div></div>
                                    <form method="POST" action="{{ route('app.billing.pause') }}">@csrf<input type="hidden" name="months" value="3"><button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/billing.pause_3_months') }}</button></form>
                                </div>

                                <div class="vy-retain-opt">
                                    <div><strong>{{ __('livewire/operator/billing.cancel_for_good_heading') }}</strong><div class="vy-line-sub">{{ __('livewire/operator/billing.cancel_for_good_body') }}</div></div>
                                    <form method="POST" action="{{ route('app.billing.cancel') }}">@csrf<button type="submit" class="vy-btn vy-btn--ghost">{{ __('livewire/operator/billing.cancel_for_good_button') }}</button></form>
                                </div>

                                <div class="vy-fx-note" style="margin-top:8px">{{ __('livewire/operator/billing.yearly_offer', ['amount' => $pricing['yearly_saving']]) }}</div>
                                <div style="text-align:right;margin-top:14px"><button type="button" class="vy-btn vy-btn--ghost" x-on:click="open = false">{{ __('livewire/operator/billing.keep_my_plan') }}</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Change plan — the real tier catalog (links to the certified Checkout) --}}
            <div id="vy-plans" style="margin-top:22px">
                <h2 class="vy-card-title vy-fade" style="margin-bottom:12px">{{ __('livewire/operator/billing.change_plan_heading') }}</h2>
                @include('livewire.operator.partials.billing-tiers')
            </div>
        @else
            {{-- ══════════ PAYWALL — lapsed / no plan ══════════ --}}
            <div class="vy-card vy-fade" style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
                <span class="vy-bill-badge vy-bill-{{ $status['active'] ? 'ok' : ($status['lapsed'] ? 'warn' : 'trial') }}"><span class="vy-status-dot"></span>{{ $status['label'] }}</span>
                <div style="flex:1">
                    @if ($status['complimentary'] && $status['on_trial'])
                        <div class="vy-bill-line">{!! __('livewire/operator/billing.complimentary_line', ['date' => e((string) $status['complimentary_until'])]) !!}</div>
                    @elseif ($status['complimentary_ended'])
                        <div class="vy-bill-line">{{ __('livewire/operator/billing.complimentary_ended_line') }}</div>
                    @elseif ($status['on_trial'])
                        <div class="vy-bill-line">{!! trans_choice('livewire/operator/billing.trial_days_left', $status['days'], ['count' => (int) $status['days']]) !!}</div>
                    @elseif ($status['trial_expired'] || $status['lapsed'])
                        <div class="vy-bill-line">{{ __('livewire/operator/billing.trial_ended') }}</div>
                    @else
                        <div class="vy-bill-line">{{ __('livewire/operator/billing.choose_a_plan') }}</div>
                    @endif
                </div>
            </div>
            @include('livewire.operator.partials.billing-tiers')
        @endif

        <p class="vy-bill-disclaimer vy-fade">{{ __('livewire/operator/billing.disclaimer') }}</p>

        @if (app()->environment('local') && ($status['on_trial'] || $status['active']))
            <form method="POST" action="{{ route('app.billing.dev-expire') }}" class="vy-fade" style="margin-top:8px">@csrf<button type="submit" class="vy-btn vy-btn--ghost" style="font-size:11px">{{ __('livewire/operator/billing.dev_expire_trial') }}</button></form>
        @endif
    </x-operator.shell>
</div>
