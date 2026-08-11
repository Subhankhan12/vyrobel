{{-- Owner Payout Method (exact-match pass). ⚠️ REAL MONEY + REAL BANKING — nothing faked. The owner captures /
     manages the bank account(s) their payouts settle to + their schedule preference + tax details. The full IBAN
     is encrypted + NEVER rendered — only the masked form (···· last4) + country (#28). Self-only (owner by user_id),
     co-owner only (the route is $capPayout).

     REAL: the account list + add / set-default / remove (encrypted, mod-97-validated) · the Stripe Connect
     onboarding (test-mode; live enablement is Subhan's cutover) · the payout preferences (shared store) · the tax &
     remittance details (real Owner fields, edited via the ONE owner.tax.save path — anti-drift + the real
     hold-release). HONEST + ACCOUNT-GATED (exact design, labelled pending the live banking connection, NEVER faked):
     automated micro-deposit verification, SEPA tracing, the 48h default-change hold — no fake "Verified"/deposit.

     Page-scoped CSS (.vy-pm-*) lives here (class names must not leak into every owner page's <style>).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="payout-method" title="Payout method" :subtitle="'Where your payouts settle'" :viewer="$viewer"
    :breadcrumb="[['label' => 'Payouts', 'url' => route('owner.payouts')], ['label' => 'Payout method']]">
    <style>
        .vy-pm-cols { display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; align-items: start; }
        @media (max-width: 1100px) { .vy-pm-cols { grid-template-columns: minmax(0, 1fr); } }
        /* Bank-account rows */
        .vy-pm-acct { display: flex; align-items: center; gap: 13px; padding: 14px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-pm-acct:last-of-type { border-bottom: none; }
        .vy-pm-acct-ic { flex: none; width: 40px; height: 40px; border-radius: var(--vy-r-md); display: grid; place-items: center; background: rgba(232, 112, 58, .12); color: var(--vy-accent-text); }
        .vy-pm-acct.is-default .vy-pm-acct-ic { background: var(--vy-grad); color: #fff; }
        .vy-pm-acct-main { flex: 1; min-width: 0; }
        .vy-pm-acct-name { font-size: 13.5px; font-weight: 600; }
        .vy-pm-acct-sub { font-size: 12px; color: var(--vy-muted); margin-top: 2px; font-variant-numeric: tabular-nums; }
        .vy-pm-radio { flex: none; width: 40px; height: 40px; border: none; background: transparent; border-radius: 50%; cursor: pointer; display: grid; place-items: center; }
        .vy-pm-radio-dot { width: 20px; height: 20px; border-radius: 50%; border: 2px solid rgba(120, 70, 40, .28); display: grid; place-items: center; transition: border-color .15s; }
        .vy-pm-radio:hover .vy-pm-radio-dot { border-color: var(--vy-accent-text); }
        .vy-pm-radio-dot.is-on { border-color: var(--vy-accent-text); }
        .vy-pm-radio-dot.is-on::after { content: ""; width: 10px; height: 10px; border-radius: 50%; background: var(--vy-grad); }
        .vy-pm-del { flex: none; width: 32px; height: 32px; border-radius: var(--vy-r-sm); border: 1px solid var(--vy-glass-border); background: rgba(255, 255, 255, .55); color: var(--vy-muted); cursor: pointer; display: grid; place-items: center; }
        .vy-pm-del:hover { color: var(--vy-accent-text); border-color: var(--vy-accent-border); }
        .vy-pm-add { width: 100%; margin-top: 14px; padding: 12px; border-radius: var(--vy-r-md); border: 1.5px dashed rgba(120, 70, 40, .28); background: rgba(255, 252, 248, .35); color: #B4400F; font-family: var(--vy-sans); font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; transition: background .14s; }
        .vy-pm-add:hover { background: rgba(255, 252, 248, .6); }
        /* Segmented radio pills (schedule + payee type) */
        .vy-pm-flabel { font-size: 11px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-on-grad-soft); margin: 16px 0 7px; }
        .vy-pm-flabel:first-child { margin-top: 0; }
        .vy-pm-seg { display: flex; gap: 4px; padding: 4px; border-radius: var(--vy-r-pill); background: rgba(120, 70, 40, .06); border: 1px solid var(--vy-glass-border); flex-wrap: wrap; }
        .vy-pm-seg-opt { flex: 1; min-width: 56px; }
        .vy-pm-seg-opt input { position: absolute; opacity: 0; pointer-events: none; }
        .vy-pm-seg-opt span { display: block; text-align: center; padding: 8px 10px; border-radius: var(--vy-r-pill); font-size: 12.5px; font-weight: 500; color: var(--vy-muted); cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; white-space: nowrap; }
        .vy-pm-seg-opt input:checked + span { background: #fff; color: var(--vy-ink); font-weight: 600; box-shadow: var(--vy-shadow-sm); }
        .vy-pm-seg-opt input:focus-visible + span { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-pm-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 560px) { .vy-pm-grid2 { grid-template-columns: 1fr; } }
        .vy-pm-summary { display: flex; align-items: center; gap: 11px; margin-top: 16px; padding: 12px 14px; border-radius: var(--vy-r-md); background: rgba(255, 252, 248, .7); border: 1px solid rgba(217, 102, 46, .18); }
        .vy-pm-summary-ic { flex: none; width: 34px; height: 34px; border-radius: var(--vy-r-sm); background: rgba(232, 112, 58, .13); color: var(--vy-accent-text); display: grid; place-items: center; }
        /* Right-rail cards */
        .vy-pm-checklist { list-style: none; margin: 10px 0 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
        .vy-pm-checklist li { display: flex; gap: 9px; font-size: 12.5px; color: var(--vy-muted); line-height: 1.5; }
        .vy-pm-checklist svg { flex: none; margin-top: 2px; color: var(--vy-accent-text); }
    </style>

    @if (session('payout_method_notice'))
        <div class="vy-flash is-ok" style="margin-bottom:16px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('payout_method_notice') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="vy-flash is-err" style="margin-bottom:16px">{{ $errors->first() }}</div>
    @endif

    @unless ($linked)
        {{-- 🔒 SG3 — the payout account is the account owner's SELF-ONLY setting (resolved by user_id). --}}
        @ownersubuser
            <div class="vy-card vy-fade"><div class="vy-empty">The payout account belongs to the account owner and is managed by them — only they can change where payouts settle.</div></div>
        @else
            <div class="vy-card vy-fade"><div class="vy-empty">No owner account is linked to your login yet.</div></div>
        @endownersubuser
    @else
        {{-- Connect Stripe banner — REAL onboarding (test-mode; live enablement flips on the account.updated webhook). --}}
        <div class="vy-card vy-fade" style="margin-bottom:18px">
            @if ($connect_enabled)
                <div style="font-size:13px;color:var(--vy-ink)"><strong style="color:rgb(31,122,90)">✓ Stripe connected</strong> — your payouts settle automatically to your connected Stripe account.</div>
            @else
                <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
                    <div style="font-size:13px;color:var(--vy-ink);max-width:440px">
                        @if ($connect_started)
                            Your Stripe onboarding isn’t finished. <strong>Continue setup</strong> to receive automated payouts.
                        @else
                            <strong>Get paid automatically.</strong> Connect a Stripe account to receive your payouts directly — Stripe handles verification; your details never touch Vyrobel.
                        @endif
                    </div>
                    <form method="POST" action="{{ route('owner.payout-method.connect') }}" style="margin:0">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--accent">{{ $connect_started ? 'Continue setup' : 'Connect Stripe' }}</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="vy-pm-cols">
            {{-- LEFT — accounts + add form + schedule + tax details --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                {{-- Bank accounts --}}
                <section class="vy-card vy-fade">
                    <h2 class="vy-card-title" style="margin-bottom:4px">Bank accounts</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 4px">Choose where monthly payouts settle. The radio marks your default account.</p>

                    @forelse ($methods as $m)
                        <div class="vy-pm-acct {{ $m['is_default'] ? 'is-default' : '' }}">
                            @if ($m['is_default'])
                                <span class="vy-pm-radio" aria-label="Default account"><span class="vy-pm-radio-dot is-on"></span></span>
                            @else
                                <form method="POST" action="{{ route('owner.payout-method.default', $m['id']) }}" style="margin:0">
                                    @csrf
                                    <button type="submit" class="vy-pm-radio" title="Make this the default account" aria-label="Set {{ $m['label'] }} as default"><span class="vy-pm-radio-dot"></span></button>
                                </form>
                            @endif
                            <span class="vy-pm-acct-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg></span>
                            <div class="vy-pm-acct-main">
                                <div class="vy-pm-acct-name">
                                    {{ $m['label'] }}
                                    @if ($m['is_default'])<span class="vy-status vy-status--paid" style="margin-left:6px"><span class="vy-dot"></span>Default</span>@endif
                                </div>
                                <div class="vy-pm-acct-sub">{{ $m['holder'] }} · IBAN {{ $m['masked'] }} · {{ $m['country'] }} · {{ $m['currency'] }}</div>
                            </div>
                            <form method="POST" action="{{ route('owner.payout-method.remove', $m['id']) }}" style="margin:0" onsubmit="return confirm('Remove this bank account?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="vy-pm-del" title="Remove this bank account" aria-label="Remove {{ $m['label'] }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg></button>
                            </form>
                        </div>
                    @empty
                        <div class="vy-empty">No bank account added yet — add one below so we know where to send your payouts.</div>
                    @endforelse

                    <div class="vy-po-pref-note" style="display:flex;align-items:flex-start;gap:7px;margin-top:14px;font-size:11.5px;color:var(--vy-on-grad-soft);line-height:1.5">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/></svg>
                        <span>Automated bank verification (micro-deposits) activates with your live banking connection — connect Stripe above for hands-off payouts.</span>
                    </div>
                </section>

                {{-- Add a bank account --}}
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <h2 class="vy-card-title" style="margin-bottom:12px">Add a bank account</h2>
                    <form method="POST" action="{{ route('owner.payout-method.add') }}">
                        @csrf
                        <div class="vy-field" style="margin-bottom:10px">
                            <label class="vy-field-label" for="account_holder">Account holder (legal name)</label>
                            <input id="account_holder" type="text" name="account_holder" value="{{ old('account_holder') }}" class="vy-input" required autocomplete="name">
                        </div>
                        <div class="vy-field" style="margin-bottom:10px">
                            <label class="vy-field-label" for="iban">IBAN</label>
                            <input id="iban" type="text" name="iban" value="{{ old('iban') }}" placeholder="e.g. PT50 0002 0123 1234 5678 9015 4" class="vy-input vy-mono" required autocomplete="off">
                            <div class="vy-line-sub" style="font-size:11px;margin-top:4px">We validate and store this securely — only the last 4 digits are ever shown.</div>
                        </div>
                        <div class="vy-pm-grid2" style="margin-bottom:14px">
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="label">Label (optional)</label>
                                <input id="label" type="text" name="label" value="{{ old('label') }}" placeholder="e.g. Revolut Business" class="vy-input">
                            </div>
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="currency">Currency</label>
                                <input id="currency" type="text" name="currency" value="{{ old('currency', $currency) }}" maxlength="3" class="vy-input" style="text-transform:uppercase" required>
                            </div>
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Save bank account</button>
                    </form>
                    <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px">Your IBAN is encrypted at rest. Your first account becomes the default automatically.</p>
                </section>

                {{-- Payout schedule — the SHARED preferences store (a preference, not a schedule engine). --}}
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <h2 class="vy-card-title" style="margin-bottom:2px">Payout schedule</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 6px">When earnings are released to your default account.</p>
                    <form method="POST" action="{{ route('owner.payouts.preferences') }}">
                        @csrf
                        <div class="vy-pm-flabel">Frequency</div>
                        <div class="vy-pm-seg">
                            {{-- WAVE 2 · #3 — ONE canonical list. These two pickers hardcoded their own arrays and had drifted:
                                 this one said "Every 2 weeks" (26 runs/yr) where the other said "Twice monthly", while
                                 PayoutSchedule actually runs the 1st and the 16th (24). --}}
                            @foreach (\App\Models\OwnerPayoutPreference::frequencyOptions() as $val => $label)
                                <label class="vy-pm-seg-opt"><input type="radio" name="frequency" value="{{ $val }}" @checked($preferences['frequency'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        <div class="vy-pm-flabel">Payout day <span style="text-transform:none;letter-spacing:0;color:var(--vy-on-grad-soft)">(business day of month)</span></div>
                        <div class="vy-pm-seg">
                            @foreach ([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th'] as $val => $label)
                                <label class="vy-pm-seg-opt"><input type="radio" name="payout_day" value="{{ $val }}" @checked($preferences['payout_day'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        <div class="vy-pm-grid2" style="margin-top:16px">
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="minimum">Minimum threshold</label>
                                <input id="minimum" type="text" name="minimum" value="{{ $preferences['minimum_minor'] > 0 ? number_format($preferences['minimum_minor'] / 100, 0, '.', '') : '0' }}" class="vy-input vy-mono" inputmode="numeric">
                                <div class="vy-line-sub" style="font-size:11px;margin-top:4px">Balances below this roll into the next payout. 0 = none.</div>
                            </div>
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="pm-currency">Currency</label>
                                <input id="pm-currency" type="text" value="{{ $currency }}" class="vy-input vy-mono" disabled aria-describedby="pm-currency-note">
                                <div id="pm-currency-note" class="vy-line-sub" style="font-size:11px;margin-top:4px">Set by your settlement region.</div>
                            </div>
                        </div>
                        <div class="vy-pm-summary">
                            <span class="vy-pm-summary-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg></span>
                            <div style="font-size:12.5px;color:#5C534C">
                                <strong>{{ \App\Models\OwnerPayoutPreference::frequencyLabel($preferences['frequency']) }} · {{ $preferences['payout_day'] }} business day.</strong>
                                @if ($nextPayout['has']) Next payout {{ $nextPayout['amount_display'] }} to your default account.@else Your next payout appears once a statement is finalized.@endif
                            </div>
                        </div>
                        <div class="vy-po-pref-note" style="display:flex;align-items:flex-start;gap:7px;margin-top:12px;font-size:11.5px;color:var(--vy-on-grad-soft);line-height:1.5">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                            <span>Your stated preference. The actual payout date + release are confirmed with your operator and the payout rail.</span>
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:14px">Save schedule</button>
                    </form>
                </section>

                {{-- Tax & remittance details — REAL Owner fields, written via the ONE owner.tax.save path (anti-drift +
                     the real hold-release). tax_id/vat are encrypted at rest (#28); shown to the owner, self-only. --}}
                <section class="vy-card vy-fade" style="animation-delay:.08s">
                    <h2 class="vy-card-title" style="margin-bottom:2px">Tax &amp; remittance details</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 6px">Printed on your remittance advice and annual summary.</p>
                    <form method="POST" action="{{ route('owner.tax.save') }}">
                        @csrf
                        <div class="vy-pm-flabel">Payee type</div>
                        <div class="vy-pm-seg" style="max-width:320px">
                            @foreach (['individual' => 'Individual', 'company' => 'Company'] as $val => $label)
                                <label class="vy-pm-seg-opt"><input type="radio" name="type" value="{{ $val }}" @checked(old('type', $tax['type']) === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        <div class="vy-field" style="margin:16px 0 0">
                            <label class="vy-field-label" for="legal_name">Registered legal name</label>
                            <input id="legal_name" type="text" name="legal_name" value="{{ old('legal_name', $tax['legal_name']) }}" class="vy-input" autocomplete="name">
                        </div>
                        <div class="vy-pm-grid2" style="margin-top:12px">
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="tax_id">Tax ID / NIF <span style="color:var(--vy-accent-text)">*</span></label>
                                <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id', $tax['tax_id']) }}" placeholder="e.g. 501234567" class="vy-input" required>
                            </div>
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="vat_number">VAT number (optional)</label>
                                <input id="vat_number" type="text" name="vat_number" value="{{ old('vat_number', $tax['vat_number']) }}" placeholder="e.g. PT501234567" class="vy-input">
                            </div>
                        </div>
                        <div class="vy-field" style="margin:12px 0 0">
                            <label class="vy-field-label" for="tax_country">Country of tax residence</label>
                            <input id="tax_country" type="text" name="tax_country" value="{{ old('tax_country', $tax['tax_country']) }}" placeholder="e.g. Portugal" class="vy-input">
                        </div>
                        <div class="vy-po-pref-note" style="display:flex;align-items:flex-start;gap:7px;margin-top:12px;font-size:11.5px;color:var(--vy-on-grad-soft);line-height:1.5">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                            <span>Saved in your Tax Center; a valid tax ID releases any held payout. Your tax ID + VAT number are stored encrypted.</span>
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:14px">Save tax details</button>
                    </form>
                </section>
            </div>

            {{-- RIGHT — where payouts go + protections + support --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                {{-- Dark PAYOUTS GO TO — the real default account + next-payout amount (no fabricated date). --}}
                <section class="vy-card vy-card--dark vy-fade">
                    <span class="vy-eyebrow">Payouts go to</span>
                    @php $default = collect($methods)->firstWhere('is_default', true); @endphp
                    @if ($default)
                        <div class="vy-po-acct" style="display:flex;align-items:center;gap:12px;margin-top:12px">
                            <span style="flex:none;width:40px;height:40px;border-radius:var(--vy-r-md);background:rgba(255,255,255,.14);display:grid;place-items:center;color:#fff"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg></span>
                            <div>
                                <div style="font-family:var(--vy-display);font-weight:600;font-size:15px">{{ $default['label'] }}</div>
                                <div class="vy-line-sub vy-mono" style="font-size:12.5px;margin-top:2px">IBAN {{ $default['masked'] }} · {{ $default['currency'] }}</div>
                            </div>
                        </div>
                        @if ($nextPayout['has'])
                            <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.12);display:flex;justify-content:space-between;align-items:baseline;gap:12px">
                                <span class="vy-line-sub">Next payout</span>
                                <span style="font-family:var(--vy-display);font-weight:600;font-size:20px;color:#fff;font-variant-numeric:tabular-nums">{{ $nextPayout['amount_display'] }}</span>
                            </div>
                        @endif
                    @else
                        <div class="vy-line-sub" style="margin-top:8px">No default account yet — add one on the left.</div>
                    @endif
                </section>

                {{-- Protected changes — honest security policy (the micro-deposit + 48h hold activate with the live rail). --}}
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <div style="display:flex;align-items:center;gap:8px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/></svg><span class="vy-card-title" style="font-size:14px">Protected changes</span></div>
                    <ul class="vy-pm-checklist">
                        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>New accounts verify via micro-deposits before any payout switch — with your live banking connection.</span></li>
                        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Changing the default holds payouts 48h as a security check — with your live banking connection.</span></li>
                        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>You’ll get an email whenever payout details change.</span></li>
                    </ul>
                </section>

                {{-- Payout not arriving? — SEPA tracing is account-gated; the honest path is your operator. --}}
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <span class="vy-card-title" style="font-size:14px">Payout not arriving?</span>
                    <p class="vy-line-sub" style="font-size:12.5px;margin-top:6px">Your account manager can trace a SEPA transfer once the live rail is connected.</p>
                    <a href="{{ route('owner.messages') }}" class="vy-btn vy-btn--ghost" style="margin-top:12px;width:100%;justify-content:center">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/></svg>
                        Contact support
                    </a>
                </section>
            </div>
        </div>
    @endunless
</x-owner.shell>
