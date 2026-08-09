{{-- Operator Billing (exact-match #85) — the operator's OWN subscription (paying Vyrobel). The active-plan
     MANAGEMENT view (plan · per-unit figure · what-you-pay · billing details · pause/cancel) + the tier
     catalog / paywall for a lapsed / no-plan org.
     HONESTY: the STATE is real (status/trial/period + the REAL read-only pause/cancel window); the per-unit
     FIGURE is real (org's real active-unit count × a PLACEHOLDER per-unit price × VAT, reconciling #17); the
     CHARGE + stored CARD + PAID invoices are account-gated — NEVER fabricated (no fake "····4242"/"Paid").
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="billing" title="Billing" :subtitle="$status['label']" :viewer="$viewer">

        {{-- Settings sub-nav (General / Team / Billing) --}}
        <div class="vy-subnav vy-fade">
            <a href="{{ route('app.settings') }}" class="vy-subnav-tab">General</a>
            <a href="{{ route('app.roles') }}" class="vy-subnav-tab">Team</a>
            <span class="vy-subnav-tab is-active">Billing</span>
        </div>

        @if (session('billing_notice'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ session('billing_notice') }}</div></div>
        @endif
        @if (session('billing_error'))
            <div class="vy-card vy-fade" style="border-left:3px solid #B4400F;margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ session('billing_error') }}</div></div>
        @endif

        {{-- Live read-only lifecycle state (canceling / paused) — the REAL state this page writes --}}
        @if ($lifecycle['is_canceling'])
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:14px">
                <div><div class="vy-bill-line"><strong>Subscription ending.</strong> You keep full access until {{ $lifecycle['readonly_from'] }}, then the workspace goes read-only. Your data is kept.</div></div>
                <form method="POST" action="{{ route('app.billing.resume') }}">@csrf<button type="submit" class="vy-btn vy-btn--accent">Keep my plan</button></form>
            </div>
        @elseif ($lifecycle['is_paused'])
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:14px">
                <div><div class="vy-bill-line"><strong>Workspace paused.</strong> Read-only until {{ $lifecycle['paused_until'] }} — resume any time.</div></div>
                <form method="POST" action="{{ route('app.billing.resume') }}">@csrf<button type="submit" class="vy-btn vy-btn--accent">Resume now</button></form>
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
                                <div class="vy-bill-line" style="margin-top:8px;color:var(--vy-muted);font-size:12.5px">
                                    {{ ucfirst($plan['interval']) }} · {{ $pricing['units'] }} {{ \Illuminate\Support\Str::plural('unit', $pricing['units']) }} × {{ $pricing['per_unit'] }} · subscribed {{ $plan['subscribed_on'] }}
                                </div>
                            </div>
                            <div class="vy-bill-price">
                                <div><span class="vy-bill-amt">{{ $pricing['gross'] }}</span><span class="vy-bill-per">/mo</span></div>
                                <div class="vy-bill-vat">incl. {{ $pricing['vat_pct'] }}% VAT</div>
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
                                <span>Next invoice <strong>{{ $plan['period_end'] }}</strong> · {{ $pricing['net'] }} + {{ $pricing['vat'] }} VAT</span>
                            </div>
                        @else
                            <div class="vy-bill-line" style="margin-top:12px;color:var(--vy-muted);font-size:12px">{{ $pricing['net'] }} + {{ $pricing['vat'] }} VAT</div>
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
                                <a href="{{ route('app.billing.checkout', ['plan' => $plan['code'], 'interval' => 'yearly']) }}" class="vy-btn vy-btn--accent">Switch to yearly · save {{ $pricing['yearly_saving'] }}</a>
                            @endif
                            <a href="#vy-plans" class="vy-btn vy-btn--ghost">Change plan</a>
                        </div>
                        <div class="vy-fx-note" style="margin-top:12px">Test mode — no card is charged until live billing is enabled. The figure is your real unit count at placeholder pricing.</div>
                    </section>

                    {{-- Invoices (HONEST — the real trial; paid invoices are account-gated) --}}
                    <section class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom:12px">Invoices</h2>
                        @foreach ($invoices as $inv)
                            <div class="vy-inv-row">
                                <div><div class="vy-inv-ref">{{ $inv['label'] }}</div><div class="vy-inv-sub">{{ $inv['period'] }}</div></div>
                                <div style="display:flex;align-items:center;gap:14px">
                                    <span class="vy-status vy-status--paid" style="font-size:11px">{{ $inv['status'] }}</span>
                                    <span class="vy-mono" style="font-weight:600">{{ $inv['amount'] }}</span>
                                </div>
                            </div>
                        @endforeach
                        <div class="vy-fx-note" style="margin-top:10px">Paid invoices &amp; PDFs appear here once live billing is enabled — nothing is charged in test mode, so no invoice is invented.</div>
                    </section>

                </div>

                {{-- Right rail --}}
                <div class="vy-stack">
                    {{-- What you're paying for (REAL counts) --}}
                    <section class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom:10px">What you’re paying for</h2>
                        <div class="vy-mini-row"><span class="vy-mini-k">Units on the rack</span><span class="vy-mini-v">{{ $counts['units'] }} × {{ $pricing['per_unit'] }}</span></div>
                        <div class="vy-mini-row"><span class="vy-mini-k">Team members</span><span class="vy-mini-v">{{ $counts['team'] }}{{ $counts['team_limit'] !== null ? ' of '.$counts['team_limit'] : ' · unlimited' }}</span></div>
                        <div class="vy-mini-row"><span class="vy-mini-k">Channels</span><span class="vy-mini-v">{{ $counts['channels'] }} connected</span></div>
                        <div class="vy-mini-row"><span class="vy-mini-k">Adding a unit</span><span class="vy-mini-v">+{{ $pricing['per_unit'] }}/mo · prorated</span></div>
                    </section>

                    {{-- Payment method (HONEST-GATED — no card stored) --}}
                    <section class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom:8px"><h2 class="vy-card-title">Payment method</h2></div>
                        <div class="vy-deferred" style="margin-top:2px">
                            <span class="vy-eyebrow">No card on file</span>
                            Your card is added on Stripe’s secure page — it never touches Vyrobel. Add one when live billing is enabled; nothing is charged in test mode.
                        </div>
                    </section>

                    {{-- Billing details (REAL + Edit) --}}
                    <section class="vy-card" x-data="{ edit: false }">
                        <div class="vy-detail-head" style="margin-bottom:8px">
                            <h2 class="vy-card-title">Billing details</h2>
                            <button type="button" class="vy-arr-link" x-on:click="edit = ! edit">Edit</button>
                        </div>
                        <div x-show="! edit">
                            <div class="vy-bill-line" style="font-weight:600">{{ $billingDetails['company'] ?? 'Not set' }}</div>
                            @if ($billingDetails['country'])<div class="vy-line-sub">{{ $billingDetails['country'] }}</div>@endif
                            @if ($billingDetails['vat_id'])<div class="vy-line-sub">VAT {{ $billingDetails['vat_id'] }}</div>@endif
                            @if ($billingDetails['email'])<div class="vy-line-sub">{{ $billingDetails['email'] }}</div>@endif
                        </div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('app.billing.company') }}" style="margin-top:6px">
                            @csrf
                            <label class="vy-field" style="display:block;margin-bottom:8px"><span class="vy-field-label">Company legal name</span><input type="text" name="billing_company" value="{{ $billingDetails['company'] }}" class="vy-input"></label>
                            <label class="vy-field" style="display:block;margin-bottom:8px"><span class="vy-field-label">VAT ID</span><input type="text" name="billing_vat_id" value="{{ $billingDetails['vat_id'] }}" class="vy-input"></label>
                            <label class="vy-field" style="display:block;margin-bottom:10px"><span class="vy-field-label">Billing email</span><input type="email" name="billing_email" value="{{ $billingDetails['email'] }}" class="vy-input"></label>
                            <button type="submit" class="vy-btn vy-btn--accent">Save</button>
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
                                    <h2 class="vy-card-title" style="font-size:13px">Cancel subscription</h2>
                                    <div class="vy-line-sub">Pause for the off-season, or cancel — your data is kept either way.</div>
                                </div>
                                @unless ($lifecycle['is_paused'] || $lifecycle['is_canceling'])
                                    <button type="button" class="vy-btn vy-btn--ghost" style="flex:none" x-on:click="open = true">Cancel…</button>
                                @endunless
                            </div>
                        </section>

                        {{-- Retention modal (Pause instead / Cancel for good / yearly offer) --}}
                        <div x-show="open" x-cloak class="vy-modal-scrim" x-on:click.self="open = false">
                            <div class="vy-modal">
                                <h3 style="font-family:var(--vy-display);font-size:18px;font-weight:600;margin:0">Before you go</h3>
                                <p class="vy-line-sub" style="margin:6px 0 16px">{{ $plan['name'] }} · {{ $pricing['units'] }} {{ \Illuminate\Support\Str::plural('unit', $pricing['units']) }}. Everything you’ve built is kept.</p>

                                <div class="vy-retain-opt">
                                    <div><strong>Pause instead · €0</strong><div class="vy-line-sub">Up to 3 months. Units, rates, owners &amp; history kept. Channels stop selling, the workspace goes read-only, and you restart with one click.</div></div>
                                    <form method="POST" action="{{ route('app.billing.pause') }}">@csrf<input type="hidden" name="months" value="3"><button type="submit" class="vy-btn vy-btn--accent">Pause 3 months</button></form>
                                </div>

                                <div class="vy-retain-opt">
                                    <div><strong>Cancel for good</strong><div class="vy-line-sub">You keep full access until your paid period ends, then the workspace is read-only, owners lose portal access, channels disconnect. Data export stays available for 90 days.</div></div>
                                    <form method="POST" action="{{ route('app.billing.cancel') }}">@csrf<button type="submit" class="vy-btn vy-btn--ghost">Cancel for good</button></form>
                                </div>

                                <div class="vy-fx-note" style="margin-top:8px">Cost the reason? Yearly billing saves {{ $pricing['yearly_saving'] }} a year. No fees either way — you can come back any time.</div>
                                <div style="text-align:right;margin-top:14px"><button type="button" class="vy-btn vy-btn--ghost" x-on:click="open = false">Keep my plan</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Change plan — the real tier catalog (links to the certified Checkout) --}}
            <div id="vy-plans" style="margin-top:22px">
                <h2 class="vy-card-title vy-fade" style="margin-bottom:12px">Change plan</h2>
                @include('livewire.operator.partials.billing-tiers')
            </div>
        @else
            {{-- ══════════ PAYWALL — lapsed / no plan ══════════ --}}
            <div class="vy-card vy-fade" style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
                <span class="vy-bill-badge vy-bill-{{ $status['active'] ? 'ok' : ($status['lapsed'] ? 'warn' : 'trial') }}"><span class="vy-status-dot"></span>{{ $status['label'] }}</span>
                <div style="flex:1">
                    @if ($status['on_trial'])
                        <div class="vy-bill-line"><strong>{{ $status['days'] }} {{ $status['days'] === 1 ? 'day' : 'days' }}</strong> left in your free trial — full access. Choose a plan to keep going after it ends.</div>
                    @elseif ($status['trial_expired'] || $status['lapsed'])
                        <div class="vy-bill-line">Your trial has ended. Choose a plan below to reactivate full access.</div>
                    @else
                        <div class="vy-bill-line">Choose a plan to subscribe.</div>
                    @endif
                </div>
            </div>
            @include('livewire.operator.partials.billing-tiers')
        @endif

        <p class="vy-bill-disclaimer vy-fade">Prices are placeholders while we finalise pricing. Subscriptions are billed securely through Stripe — your card details never touch Vyrobel, and no charge is taken in test mode.</p>

        @if (app()->environment('local') && ($status['on_trial'] || $status['active']))
            <form method="POST" action="{{ route('app.billing.dev-expire') }}" class="vy-fade" style="margin-top:8px">@csrf<button type="submit" class="vy-btn vy-btn--ghost" style="font-size:11px">Test only · expire trial</button></form>
        @endif
    </x-operator.shell>
</div>
