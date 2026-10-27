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
<x-owner.shell active="payout-method" :title="__('livewire/owner/payout-method.title')" :subtitle="__('livewire/owner/payout-method.subtitle')" :viewer="$viewer"
    :breadcrumb="[['label' => __('livewire/owner/payout-method.breadcrumb_payouts'), 'url' => route('owner.payouts')], ['label' => __('livewire/owner/payout-method.title')]]">
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

    {{-- ⚠️ THE FOURTH DOOR, found by sweeping this class rather than by report. `OwnerConnectController`
         flashes `payout_method_error` ("We couldn't start Stripe onboarding") to THIS screen, and this view
         read only the NOTICE key — so a failed Stripe onboarding looked like nothing happening at all, on
         the screen where an owner sets up where their money goes. Same class as F-1/F-4/F-5. --}}
    @if (session('payout_method_error'))
        <div class="vy-flash is-err" role="alert" style="margin-bottom:14px;">{{ session('payout_method_error') }}</div>
    @endif
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
            <div class="vy-card vy-fade"><div class="vy-empty">{{ __('livewire/owner/payout-method.not_account_owner') }}</div></div>
        @else
            <div class="vy-card vy-fade"><div class="vy-empty">{{ __('livewire/owner/payout-method.no_owner_linked') }}</div></div>
        @endownersubuser
    @else
        {{-- Connect Stripe banner — REAL onboarding (test-mode; live enablement flips on the account.updated webhook). --}}
        <div class="vy-card vy-fade" style="margin-bottom:18px">
            @if ($connect_enabled)
                <div style="font-size:13px;color:var(--vy-ink)">{!! __('livewire/owner/payout-method.stripe_connected') !!}</div>
            @else
                <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
                    <div style="font-size:13px;color:var(--vy-ink);max-width:440px">
                        @if ($connect_started)
                            {!! __('livewire/owner/payout-method.stripe_unfinished') !!}
                        @else
                            {!! __('livewire/owner/payout-method.stripe_get_paid') !!}
                        @endif
                    </div>
                    <form method="POST" action="{{ route('owner.payout-method.connect') }}" style="margin:0">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--accent">{{ $connect_started ? __('livewire/owner/payout-method.continue_setup') : __('livewire/owner/payout-method.connect_stripe') }}</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="vy-pm-cols">
            {{-- LEFT — accounts + add form + schedule + tax details --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                {{-- Bank accounts --}}
                <section class="vy-card vy-fade">
                    <h2 class="vy-card-title" style="margin-bottom:4px">{{ __('livewire/owner/payout-method.bank_accounts_heading') }}</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 4px">{{ __('livewire/owner/payout-method.bank_accounts_sub') }}</p>

                    @forelse ($methods as $m)
                        <div class="vy-pm-acct {{ $m['is_default'] ? 'is-default' : '' }}">
                            @if ($m['is_default'])
                                <span class="vy-pm-radio" aria-label="{{ __('livewire/owner/payout-method.default_account_aria') }}"><span class="vy-pm-radio-dot is-on"></span></span>
                            @else
                                {{-- ⚠️ RE-AUTH ON THE REDIRECT. Setting a different account as default IS the payout
                                     redirect — an attacker who can only ADD an account has achieved nothing. --}}
                                <div x-data="{ ask: false }" style="margin:0">
                                    <button type="button" class="vy-pm-radio" title="{{ __('livewire/owner/payout-method.make_default_title') }}"
                                            aria-label="{{ __('livewire/owner/payout-method.set_default_aria', ['label' => $m['label']]) }}" @click="ask = !ask"><span class="vy-pm-radio-dot"></span></button>
                                    <form x-show="ask" x-cloak method="POST" action="{{ route('owner.payout-method.default', $m['id']) }}"
                                          style="position:absolute;z-index:5;background:var(--vy-surface,#fff);border:1px solid var(--vy-hairline);border-radius:10px;padding:10px;margin-top:6px;min-width:230px">
                                        @csrf
                                        <label class="vy-pm-flabel" for="def_pw_{{ $m['id'] }}">{{ __('livewire/owner/payout-method.confirm_with_password') }}</label>
                                        <input id="def_pw_{{ $m['id'] }}" type="password" name="current_password" class="vy-input"
                                               autocomplete="current-password" required aria-label="{{ __('livewire/owner/payout-method.current_password_aria') }}">
                                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;margin-top:8px">{{ __('livewire/owner/payout-method.make_default') }}</button>
                                    </form>
                                </div>
                            @endif
                            <span class="vy-pm-acct-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg></span>
                            <div class="vy-pm-acct-main">
                                <div class="vy-pm-acct-name">
                                    {{ $m['label'] }}
                                    @if ($m['is_default'])<span class="vy-status vy-status--paid" style="margin-left:6px"><span class="vy-dot"></span>{{ __('livewire/owner/payout-method.default_badge') }}</span>@endif
                                </div>
                                <div class="vy-pm-acct-sub">{{ __('livewire/owner/payout-method.account_sub', ['holder' => $m['holder'], 'masked' => $m['masked'], 'country' => $m['country'], 'currency' => $m['currency']]) }}</div>
                            </div>
                            {{-- ⚠️ RE-AUTH ON REMOVAL. Deleting the genuine account leaves whatever remains as
                                 the payout destination, so removal redirects the money just as surely. --}}
                            <div x-data="{ ask: false }" style="margin:0">
                                <button type="button" class="vy-pm-del" title="{{ __('livewire/owner/payout-method.remove_account_title') }}"
                                        aria-label="{{ __('livewire/owner/payout-method.remove_aria', ['label' => $m['label']]) }}" @click="ask = !ask"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg></button>
                                <form x-show="ask" x-cloak method="POST" action="{{ route('owner.payout-method.remove', $m['id']) }}"
                                      style="position:absolute;right:0;z-index:5;background:var(--vy-surface,#fff);border:1px solid var(--vy-hairline);border-radius:10px;padding:10px;margin-top:6px;min-width:230px">
                                    @csrf
                                    @method('DELETE')
                                    <label class="vy-pm-flabel" for="rm_pw_{{ $m['id'] }}">{{ __('livewire/owner/payout-method.confirm_with_password') }}</label>
                                    <input id="rm_pw_{{ $m['id'] }}" type="password" name="current_password" class="vy-input"
                                           autocomplete="current-password" required aria-label="{{ __('livewire/owner/payout-method.current_password_aria') }}">
                                    <button type="submit" class="vy-btn" style="width:100%;margin-top:8px;color:#B4400F">{{ __('livewire/owner/payout-method.remove_account') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">{{ __('livewire/owner/payout-method.no_accounts') }}</div>
                    @endforelse

                    <div class="vy-po-pref-note" style="display:flex;align-items:flex-start;gap:7px;margin-top:14px;font-size:11.5px;color:var(--vy-on-grad-soft);line-height:1.5">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/></svg>
                        <span>{{ __('livewire/owner/payout-method.verification_pending_live') }}</span>
                    </div>
                </section>

                {{-- Add a bank account --}}
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <h2 class="vy-card-title" style="margin-bottom:12px">{{ __('livewire/owner/payout-method.add_heading') }}</h2>
                    <form method="POST" action="{{ route('owner.payout-method.add') }}">
                        @csrf
                        <div class="vy-field" style="margin-bottom:10px">
                            <label class="vy-field-label" for="account_holder">{{ __('livewire/owner/payout-method.account_holder_label') }}</label>
                            <input id="account_holder" type="text" name="account_holder" value="{{ old('account_holder') }}" class="vy-input" required autocomplete="name">
                        </div>
                        <div class="vy-field" style="margin-bottom:10px">
                            <label class="vy-field-label" for="iban">{{ __('livewire/owner/payout-method.iban_label') }}</label>
                            <input id="iban" type="text" name="iban" value="{{ old('iban') }}" placeholder="{{ __('livewire/owner/payout-method.iban_placeholder') }}" class="vy-input vy-mono" required autocomplete="off">
                            <div class="vy-line-sub" style="font-size:11px;margin-top:4px">{{ __('livewire/owner/payout-method.iban_help') }}</div>
                        </div>
                        <div class="vy-pm-grid2" style="margin-bottom:14px">
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="label">{{ __('livewire/owner/payout-method.label_label') }}</label>
                                <input id="label" type="text" name="label" value="{{ old('label') }}" placeholder="{{ __('livewire/owner/payout-method.label_placeholder') }}" class="vy-input">
                            </div>
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="currency">{{ __('livewire/owner/payout-method.currency') }}</label>
                                <input id="currency" type="text" name="currency" value="{{ old('currency', $currency) }}" maxlength="3" class="vy-input" style="text-transform:uppercase" required>
                            </div>
                        </div>
                        {{-- ⚠️ RE-AUTH (audit follow-up). Changing where money is sent requires the current
                             password — the same control the account-email change carries. --}}
                        <div class="vy-pm-flabel" style="margin-top:10px">{{ __('livewire/owner/payout-method.confirm_with_password') }}</div>
                        <input type="password" name="current_password" class="vy-input"
                               autocomplete="current-password" required aria-label="{{ __('livewire/owner/payout-method.current_password_aria') }}">
                        @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:10px">{{ __('livewire/owner/payout-method.save_bank_account') }}</button>
                    </form>
                    <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px">{{ __('livewire/owner/payout-method.iban_encrypted_note') }}</p>
                </section>

                {{-- Payout schedule — the SHARED preferences store (a preference, not a schedule engine). --}}
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <h2 class="vy-card-title" style="margin-bottom:2px">{{ __('livewire/owner/payout-method.schedule_heading') }}</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 6px">{{ __('livewire/owner/payout-method.schedule_sub') }}</p>
                    <form method="POST" action="{{ route('owner.payouts.preferences') }}">
                        @csrf
                        <div class="vy-pm-flabel">{{ __('livewire/owner/payout-method.frequency') }}</div>
                        <div class="vy-pm-seg">
                            {{-- WAVE 2 · #3 — ONE canonical list. These two pickers hardcoded their own arrays and had drifted:
                                 this one said "Every 2 weeks" (26 runs/yr) where the other said "Twice monthly", while
                                 PayoutSchedule actually runs the 1st and the 16th (24). --}}
                            @foreach (\App\Models\OwnerPayoutPreference::frequencyOptions() as $val => $label)
                                <label class="vy-pm-seg-opt"><input type="radio" name="frequency" value="{{ $val }}" @checked($preferences['frequency'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        <div class="vy-pm-flabel">{!! __('livewire/owner/payout-method.payout_day_label') !!}</div>
                        <div class="vy-pm-seg">
                            @foreach ([1 => __('livewire/owner/payout-method.day_1st'), 2 => __('livewire/owner/payout-method.day_2nd'), 3 => __('livewire/owner/payout-method.day_3rd'), 4 => __('livewire/owner/payout-method.day_4th'), 5 => __('livewire/owner/payout-method.day_5th')] as $val => $label)
                                <label class="vy-pm-seg-opt"><input type="radio" name="payout_day" value="{{ $val }}" @checked($preferences['payout_day'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        <div class="vy-pm-grid2" style="margin-top:16px">
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="minimum">{{ __('livewire/owner/payout-method.minimum_threshold') }}</label>
                                <input id="minimum" type="text" name="minimum" value="{{ $preferences['minimum_minor'] > 0 ? number_format($preferences['minimum_minor'] / 100, 0, '.', '') : '0' }}" class="vy-input vy-mono" inputmode="numeric">
                                <div class="vy-line-sub" style="font-size:11px;margin-top:4px">{{ __('livewire/owner/payout-method.minimum_help') }}</div>
                            </div>
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="pm-currency">{{ __('livewire/owner/payout-method.currency') }}</label>
                                <input id="pm-currency" type="text" value="{{ $currency }}" class="vy-input vy-mono" disabled aria-describedby="pm-currency-note">
                                <div id="pm-currency-note" class="vy-line-sub" style="font-size:11px;margin-top:4px">{{ __('livewire/owner/payout-method.currency_note') }}</div>
                            </div>
                        </div>
                        <div class="vy-pm-summary">
                            <span class="vy-pm-summary-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg></span>
                            <div style="font-size:12.5px;color:#5C534C">
                                <strong>{{ __('livewire/owner/payout-method.schedule_summary', ['frequency' => \App\Models\OwnerPayoutPreference::frequencyLabel($preferences['frequency']), 'day' => $preferences['payout_day']]) }}</strong>
                                @if ($nextPayout['has']) {{ __('livewire/owner/payout-method.next_payout_to_default', ['amount' => $nextPayout['amount_display']]) }}@else {{ __('livewire/owner/payout-method.next_payout_pending') }}@endif
                            </div>
                        </div>
                        <div class="vy-po-pref-note" style="display:flex;align-items:flex-start;gap:7px;margin-top:12px;font-size:11.5px;color:var(--vy-on-grad-soft);line-height:1.5">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                            <span>{{ __('livewire/owner/payout-method.schedule_preference_note') }}</span>
                        </div>
                        {{-- ⚠️ RE-AUTH: pausing stops your payouts entirely, and a high minimum is a pause by another name. --}}
                        <div class="vy-field" style="margin:14px 0 0">
                            <label class="vy-field-label" for="pm_prefs_pw">{{ __('livewire/owner/payout-method.confirm_with_password') }}</label>
                            <input id="pm_prefs_pw" type="password" name="current_password" class="vy-input"
                                   autocomplete="current-password" required aria-label="{{ __('livewire/owner/payout-method.current_password_aria') }}">
                            @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:14px">{{ __('livewire/owner/payout-method.save_schedule') }}</button>
                    </form>
                </section>

                {{-- Tax & remittance details — REAL Owner fields, written via the ONE owner.tax.save path (anti-drift +
                     the real hold-release). tax_id/vat are encrypted at rest (#28); shown to the owner, self-only. --}}
                <section class="vy-card vy-fade" style="animation-delay:.08s">
                    <h2 class="vy-card-title" style="margin-bottom:2px">{{ __('livewire/owner/payout-method.tax_heading') }}</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 6px">{{ __('livewire/owner/payout-method.tax_sub') }}</p>
                    <form method="POST" action="{{ route('owner.tax.save') }}">
                        @csrf
                        <div class="vy-pm-flabel">{{ __('livewire/owner/payout-method.payee_type') }}</div>
                        <div class="vy-pm-seg" style="max-width:320px">
                            @foreach (['individual' => __('livewire/owner/payout-method.payee_individual'), 'company' => __('livewire/owner/payout-method.payee_company')] as $val => $label)
                                <label class="vy-pm-seg-opt"><input type="radio" name="type" value="{{ $val }}" @checked(old('type', $tax['type']) === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        <div class="vy-field" style="margin:16px 0 0">
                            <label class="vy-field-label" for="legal_name">{{ __('livewire/owner/payout-method.legal_name_label') }}</label>
                            <input id="legal_name" type="text" name="legal_name" value="{{ old('legal_name', $tax['legal_name']) }}" class="vy-input" autocomplete="name">
                        </div>
                        <div class="vy-pm-grid2" style="margin-top:12px">
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="tax_id">{{ __('livewire/owner/payout-method.tax_id_label') }} <span style="color:var(--vy-accent-text)">*</span></label>
                                <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id', $tax['tax_id']) }}" placeholder="{{ __('livewire/owner/payout-method.tax_id_placeholder') }}" class="vy-input" required>
                            </div>
                            <div class="vy-field" style="margin:0">
                                <label class="vy-field-label" for="vat_number">{{ __('livewire/owner/payout-method.vat_number_label') }}</label>
                                <input id="vat_number" type="text" name="vat_number" value="{{ old('vat_number', $tax['vat_number']) }}" placeholder="{{ __('livewire/owner/payout-method.vat_number_placeholder') }}" class="vy-input">
                            </div>
                        </div>
                        <div class="vy-field" style="margin:12px 0 0">
                            <label class="vy-field-label" for="tax_country">{{ __('livewire/owner/payout-method.tax_country_label') }}</label>
                            <input id="tax_country" type="text" name="tax_country" value="{{ old('tax_country', $tax['tax_country']) }}" placeholder="{{ __('livewire/owner/payout-method.tax_country_placeholder') }}" class="vy-input">
                        </div>
                        <div class="vy-po-pref-note" style="display:flex;align-items:flex-start;gap:7px;margin-top:12px;font-size:11.5px;color:var(--vy-on-grad-soft);line-height:1.5">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                            <span>{{ __('livewire/owner/payout-method.tax_saved_note') }}</span>
                        </div>
                        {{-- ⚠️ RE-AUTH: this changes the payee name on your remittance document and the residency your withholding is computed from. --}}
                        <div class="vy-field" style="margin:14px 0 0">
                            <label class="vy-field-label" for="pm_tax_pw">{{ __('livewire/owner/payout-method.confirm_with_password') }}</label>
                            <input id="pm_tax_pw" type="password" name="current_password" class="vy-input"
                                   autocomplete="current-password" required aria-label="{{ __('livewire/owner/payout-method.current_password_aria') }}">
                            @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:14px">{{ __('livewire/owner/payout-method.save_tax_details') }}</button>
                    </form>
                </section>
            </div>

            {{-- RIGHT — where payouts go + protections + support --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                {{-- Dark PAYOUTS GO TO — the real default account + next-payout amount (no fabricated date). --}}
                <section class="vy-card vy-card--dark vy-fade">
                    <span class="vy-eyebrow">{{ __('livewire/owner/payout-method.payouts_go_to') }}</span>
                    @php $default = collect($methods)->firstWhere('is_default', true); @endphp
                    @if ($default)
                        <div class="vy-po-acct" style="display:flex;align-items:center;gap:12px;margin-top:12px">
                            <span style="flex:none;width:40px;height:40px;border-radius:var(--vy-r-md);background:rgba(255,255,255,.14);display:grid;place-items:center;color:#fff"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg></span>
                            <div>
                                <div style="font-family:var(--vy-display);font-weight:600;font-size:15px">{{ $default['label'] }}</div>
                                <div class="vy-line-sub vy-mono" style="font-size:12.5px;margin-top:2px">{{ __('livewire/owner/payout-method.default_account_sub', ['masked' => $default['masked'], 'currency' => $default['currency']]) }}</div>
                            </div>
                        </div>
                        @if ($nextPayout['has'])
                            <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.12);display:flex;justify-content:space-between;align-items:baseline;gap:12px">
                                <span class="vy-line-sub">{{ __('livewire/owner/payout-method.next_payout') }}</span>
                                <span style="font-family:var(--vy-display);font-weight:600;font-size:20px;color:#fff;font-variant-numeric:tabular-nums">{{ $nextPayout['amount_display'] }}</span>
                            </div>
                        @endif
                    @else
                        <div class="vy-line-sub" style="margin-top:8px">{{ __('livewire/owner/payout-method.no_default_account') }}</div>
                    @endif
                </section>

                {{-- Protected changes — honest security policy (the micro-deposit + 48h hold activate with the live rail). --}}
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <div style="display:flex;align-items:center;gap:8px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/></svg><span class="vy-card-title" style="font-size:14px">{{ __('livewire/owner/payout-method.protected_heading') }}</span></div>
                    <ul class="vy-pm-checklist">
                        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>{{ __('livewire/owner/payout-method.protected_micro_deposits') }}</span></li>
                        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>{{ __('livewire/owner/payout-method.protected_48h_hold') }}</span></li>
                        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>{{ __('livewire/owner/payout-method.protected_email') }}</span></li>
                    </ul>
                </section>

                {{-- Payout not arriving? — SEPA tracing is account-gated; the honest path is your operator. --}}
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <span class="vy-card-title" style="font-size:14px">{{ __('livewire/owner/payout-method.not_arriving_heading') }}</span>
                    <p class="vy-line-sub" style="font-size:12.5px;margin-top:6px">{{ __('livewire/owner/payout-method.not_arriving_body') }}</p>
                    <a href="{{ route('owner.messages') }}" class="vy-btn vy-btn--ghost" style="margin-top:12px;width:100%;justify-content:center">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/></svg>
                        {{ __('livewire/owner/payout-method.contact_support') }}
                    </a>
                </section>
            </div>
        </div>
    @endunless
</x-owner.shell>
