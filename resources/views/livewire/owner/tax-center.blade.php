{{-- Owner Tax Center (exact-match pass · invariant #64) — rebuilt to the decoded "Owner Tax Center" wireframe on
     REAL, ALREADY-BUILT backends (anti-drift, not rebuilt):
       • the TAX-HOLD (the SAME hold as Payouts/Balance — real pending payouts held until a tax ID is on file);
       • the TAX STORE `owner.tax.save` (the SAME path Settings/Payouts write — encrypted #28, audited, self-only,
         $capActions #20; saving a tax_id RELEASES the real hold);
       • the ANNUAL TAX PDF `owner.tax.annual` (reconciling #17, $capExports #42) + the Documents archive.
     WITHHOLDING IS REAL (Wave D · tax engine, #73) — the card shows the owner's ACTUAL withheld total, Σ
     `withholding_minor` over their committed statements, and an honest "None" while no rate is configured for
     their residency. ⚠️ This line used to read "WITHHOLDING is NOT modelled", which went stale when the engine
     shipped. What stays refused is the wireframe's fabricated 15%/20% RATE (#39): the page prints an AMOUNT,
     never a rate — the same rule as the annual PDF.
     The "VAT registered" toggle stores a REGISTRATION FLAG (vat_registered) — NOT a VAT computation.
     RAW-BLADE RULE: every `@` follows a space, newline or tag (unregistered @click/@js pass through to Alpine). --}}
<x-owner.shell active="tax" title="Tax center" :subtitle="'Your tax details, withholding and year-end summaries — all in one place.'" :viewer="$viewer">
    <style>
        .vy-tax-banner { display:flex; align-items:flex-start; gap:14px; padding:16px 18px; border-radius:18px; margin-bottom:20px; box-shadow:0 14px 40px rgba(150,90,50,.14), inset 0 1px 0 rgba(255,255,255,.55); }
        .vy-tax-banner--warn { background:rgba(194,65,12,.10); border:1px solid rgba(194,65,12,.28); }
        .vy-tax-banner--ok { background:rgba(79,122,70,.11); border:1px solid rgba(79,122,70,.26); align-items:center; }
        .vy-tax-banner-ic { flex:none; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; }
        .vy-tax-banner--warn .vy-tax-banner-ic { background:linear-gradient(140deg,#E8703A,#B4400F); box-shadow:0 4px 12px rgba(194,65,12,.3); }
        .vy-tax-banner--ok .vy-tax-banner-ic { width:38px; height:38px; background:linear-gradient(140deg,#6FA764,#4F7A46); box-shadow:0 4px 12px rgba(79,122,70,.3); }
        .vy-tax-banner-t { font-family:var(--vy-display); font-weight:600; font-size:15px; letter-spacing:-.01em; }
        .vy-tax-banner--warn .vy-tax-banner-t { color:#8A3410; }
        .vy-tax-banner--ok .vy-tax-banner-t { color:#3F6A38; }
        .vy-tax-banner-s { font-size:12.5px; line-height:1.5; margin-top:5px; }
        .vy-tax-banner--warn .vy-tax-banner-s { color:#7A4A2E; }
        .vy-tax-banner--ok .vy-tax-banner-s { color:#4F6A48; }

        .vy-tax-row { display:flex; flex-wrap:wrap; gap:20px; align-items:flex-start; }
        .vy-tax-left { flex:999 1 380px; min-width:0; display:flex; flex-direction:column; gap:20px; }
        .vy-tax-right { flex:1 1 300px; min-width:280px; display:flex; flex-direction:column; gap:20px; }

        .vy-tax-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px 18px; }
        .vy-tax-grid .full { grid-column:1 / -1; }
        .vy-tax-lbl { font-size:12px; font-weight:500; color:var(--vy-ink); margin-bottom:6px; display:block; text-transform:none; letter-spacing:0; }

        .vy-seg { display:inline-flex; padding:4px; border-radius:12px; background:rgba(120,70,40,.08); gap:4px; }
        .vy-seg button { padding:8px 18px; border-radius:9px; border:none; cursor:pointer; font-family:var(--vy-sans); font-size:13px; font-weight:500; background:transparent; color:var(--vy-ink); transition:all .14s; }
        .vy-seg button.is-on { background:#fff; color:var(--vy-ink); box-shadow:var(--vy-shadow-sm); }

        .vy-tax-vatcard { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 15px; border-radius:13px; background:rgba(255,255,255,.5); border:1px solid var(--vy-glass-border); }
        .vy-toggle { width:48px; height:28px; border-radius:30px; border:none; cursor:pointer; position:relative; background:rgba(120,70,40,.22); transition:background .18s; flex-shrink:0; }
        .vy-toggle.is-on { background:var(--vy-grad); }
        .vy-toggle .knob { position:absolute; top:3px; left:3px; width:22px; height:22px; border-radius:50%; background:#fff; box-shadow:0 2px 6px rgba(0,0,0,.2); transition:left .18s; }
        .vy-toggle.is-on .knob { left:23px; }

        .vy-tax-with-fig { font-family:var(--vy-display); font-weight:600; font-size:34px; letter-spacing:-.02em; color:var(--vy-accent-text); font-variant-numeric:tabular-nums; line-height:1; }

        .vy-tax-sumrow { display:flex; align-items:center; gap:13px; padding:13px 14px; border-radius:14px; background:rgba(255,255,255,.5); border:1px solid var(--vy-glass-border); text-decoration:none; color:var(--vy-ink); transition:background .14s, transform .14s; }
        .vy-tax-sumrow:hover { background:rgba(255,255,255,.85); transform:translateY(-1px); }
        .vy-tax-sum-ic { width:38px; height:38px; border-radius:10px; background:rgba(217,102,46,.13); color:#B4400F; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .vy-tax-sum-tag { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:500; }

        .vy-tax-docs { display:flex; align-items:center; gap:13px; padding:18px 20px; border-radius:18px; background:var(--vy-glass); backdrop-filter:blur(22px) saturate(1.15); border:1px solid var(--vy-glass-border); box-shadow:var(--vy-shadow); text-decoration:none; transition:transform .14s; }
        .vy-tax-docs:hover { transform:translateY(-2px); }
        .vy-tax-docs-ic { width:40px; height:40px; border-radius:11px; background:rgba(120,70,40,.09); color:#8A5A3A; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    </style>

    @if (session('tax_notice'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('tax_notice') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber, #b8860b);margin-bottom:16px">
            <div style="font-size:13px;color:var(--vy-ink)">{{ $errors->first() }}</div>
        </div>
    @endif

    @unless ($linked)
        {{-- 🔒 SELF-ONLY — the tax profile belongs to the account owner (resolved by user_id). A sub-user has no
             owner row of their own → tell them honestly, never a bare "not linked" and never another owner's data. --}}
        @ownersubuser
            <div class="vy-card vy-fade"><div class="vy-empty">The tax profile belongs to the account owner and is managed by them — it isn’t part of your team role.</div></div>
        @else
            <div class="vy-card vy-fade"><div class="vy-empty">No owner account is linked to your login yet.</div></div>
        @endownersubuser
    @else
        {{-- ── Status banner — REAL two states ── --}}
        @if ($complete)
            <div class="vy-tax-banner vy-tax-banner--ok vy-fade">
                <span class="vy-tax-banner-ic" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg></span>
                <div>
                    <div class="vy-tax-banner-t">Tax details complete</div>
                    <div class="vy-tax-banner-s">Your payouts are flowing normally. Update anytime below.</div>
                </div>
            </div>
        @else
            <div class="vy-tax-banner vy-tax-banner--warn vy-fade">
                <span class="vy-tax-banner-ic" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16.5v.5"/><path d="M10.3 3.9 2.4 18a1.8 1.8 0 0 0 1.6 2.7h16a1.8 1.8 0 0 0 1.6-2.7L13.7 3.9a1.8 1.8 0 0 0-3.4 0z"/></svg></span>
                <div style="flex:1;min-width:0">
                    <div class="vy-tax-banner-t">Add your tax details to release held payouts</div>
                    <div class="vy-tax-banner-s">
                        @if ($held !== [])
                            @foreach ($held as $h){{ $h['display'] }}@if (! $loop->last) · @endif @endforeach
                            is on hold until we have a valid tax ID on file. It takes about a minute — just complete the form below.
                        @else
                            Complete the form below so your payouts can be released the moment your first statement is due — it takes about a minute.
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="vy-tax-row">
            {{-- ── LEFT: tax profile + withholding ── --}}
            <div class="vy-tax-left">
                {{-- Tax profile form — REUSE owner.tax.save (encrypted #28, audited, $capActions #20) --}}
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <h2 class="vy-card-title" style="font-size:17px;margin-bottom:4px">Tax profile</h2>
                    <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0 0 20px">Used on your annual summary and any invoices we issue on your behalf.</p>

                    <form method="POST" action="{{ route('owner.tax.save') }}" x-data="{ type: @js($profile['type'] ?: 'individual'), vatReg: @js($profile['vat_registered']) }">
                        @csrf
                        <input type="hidden" name="type" :value="type">
                        <input type="hidden" name="vat_registered" :value="vatReg ? 1 : 0">

                        <div class="vy-tax-grid">
                            <div class="full">
                                <label class="vy-tax-lbl" for="legal_name">Legal name</label>
                                <input id="legal_name" type="text" name="legal_name" value="{{ old('legal_name', $profile['legal_name']) }}" placeholder="Full legal name" class="vy-input" style="width:100%" autocomplete="name">
                            </div>

                            <div>
                                <label class="vy-tax-lbl" for="tax_country">Tax residency</label>
                                {{-- ⚠️ THE OPTION VALUE IS THE ISO CODE, THE LABEL IS THE NAME (audit 2026-08-07).
                                     This select used to emit `value="Portugal"`, but `owners.tax_country` is an ISO
                                     code by contract — it is the key WithholdingService and OwnerVatService look a
                                     rate up by (`tax:set-rate PT …`). A name matched nothing, so choosing a
                                     residency here silently produced a ZERO withholding on a real payout. Codes
                                     come from the ONE shared list so this cannot drift from the display side. --}}
                                @php $selectedCountry = \App\Support\Countries::toCode(old('tax_country', $profile['tax_country'])); @endphp
                                <select id="tax_country" name="tax_country" class="vy-input" style="width:100%;cursor:pointer">
                                    {{-- ⚠️ "Not listed" IS A COMPLETABLE CHOICE, and saying so is the fix.
                                         The residency list is deliberately the set the product serves, so an
                                         owner resident elsewhere has no option to pick. Leaving this blank was
                                         ALWAYS allowed — `tax_country` is `nullable` and the payout hold is
                                         released by `Owner::hasTaxDetails()`, which reads `tax_id` ALONE — but
                                         a bare em-dash reads as "unset", so an owner could believe the form was
                                         un-completable and never save the tax id that releases their payout.
                                         ⚠️ NO "Other" OPTION: `tax_country` is an ISO-code lookup key for
                                         TaxRateService::rateFor(), and Countries::toCode() passes unrecognised
                                         values THROUGH — so "Other" would be stored as a junk code matching no
                                         configured rate, behaving exactly like blank while LOOKING like a
                                         recorded residency. Blank is the honest representation of "not listed". --}}
                                    <option value="">Not listed — leave blank</option>
                                    @foreach (\App\Support\Countries::options() as $code => $name)
                                        <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $name }}</option>
                                    @endforeach
                                    {{-- A stored value this list does not know still renders selected, rather than
                                         silently resetting the owner's residency to blank on the next save. --}}
                                    @if ($selectedCountry !== null && ! \App\Support\Countries::isKnownCode($selectedCountry))
                                        <option value="{{ $selectedCountry }}" selected>{{ $selectedCountry }}</option>
                                    @endif
                                </select>
                            </div>

                            <div>
                                <label class="vy-tax-lbl" for="tax_id">Tax ID / NIF <span style="color:var(--vy-ink)">*</span></label>
                                <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id', $profile['tax_id']) }}" placeholder="e.g. 501234567" class="vy-input" style="width:100%" required>
                            </div>

                            <div class="full">
                                <label class="vy-tax-lbl">Registered as</label>
                                <div class="vy-seg" role="group" aria-label="Registered as">
                                    <button type="button" :class="type === 'individual' ? 'is-on' : ''" @click="type = 'individual'">Individual</button>
                                    <button type="button" :class="type === 'company' ? 'is-on' : ''" @click="type = 'company'">Company</button>
                                </div>
                            </div>

                            <div class="full vy-tax-vatcard">
                                <div>
                                    <div style="font-size:13.5px;font-weight:500;color:var(--vy-ink)">VAT registered</div>
                                    <div style="font-size:12px;color:var(--vy-ink);margin-top:2px">Turn on if you charge VAT on your rental income.</div>
                                </div>
                                <button type="button" class="vy-toggle" :class="vatReg ? 'is-on' : ''" @click="vatReg = ! vatReg" :aria-pressed="vatReg ? 'true' : 'false'" aria-label="VAT registered"><span class="knob"></span></button>
                            </div>

                            <div class="full" x-show="vatReg" x-cloak>
                                <label class="vy-tax-lbl" for="vat_number">VAT number</label>
                                <input id="vat_number" type="text" name="vat_number" value="{{ old('vat_number', $profile['vat_number']) }}" placeholder="e.g. PT501234567" class="vy-input" style="width:100%">
                            </div>
                        </div>

                        {{-- 🔒 an accountant may READ tax & documents but SAVING is co-owner-only ($capActions);
                             gating the control too (never invite a 403). --}}
                        <div style="margin-top:22px">
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <button type="submit" class="vy-btn vy-btn--accent" style="padding:11px 22px">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                                    Save tax details
                                </button>
                            @else
                                <p class="vy-line-sub" style="font-size:12px;margin:0">Read-only — a co-owner can update these tax details.</p>
                            @endownercan
                        </div>
                    </form>
                    <p class="vy-line-sub" style="font-size:11.5px;margin-top:12px">Your tax ID and VAT number are encrypted at rest. Saving them releases any held payouts.</p>
                </section>

                {{-- Withholding — REAL (Wave D): the withheld total from configured rates, or an honest "None" when
                     no withholding rate is configured for your residency. Never a fabricated rate (#39). --}}
                <section class="vy-card vy-fade" style="animation-delay:.08s">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                        <div style="flex:1;min-width:220px">
                            <h2 class="vy-card-title" style="font-size:16px;margin-bottom:4px">Withholding tax</h2>
                            @if ($withholding['has'])
                                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;line-height:1.55;margin:0">Tax withheld from your payouts at the rate configured for your residency and remitted on your behalf. <strong>Verify with your accountant — this is not tax advice.</strong></p>
                            @else
                                <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;line-height:1.55;margin:0">No withholding is configured for your residency — you receive your full net earnings and declare your rental income in your own tax return.</p>
                            @endif
                        </div>
                        <div style="text-align:right">
                            <div class="vy-tax-with-fig">{{ $withholding['has'] ? $withholding['display'] : 'None' }}</div>
                            <div style="font-size:11px;color:var(--vy-faint);margin-top:4px">withheld from payouts</div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ── RIGHT: annual summaries + documents + about ── --}}
            <div class="vy-tax-right">
                {{-- Annual summaries — REAL per-year gross/net + the real annual PDF (reconciles #17, $capExports #42) --}}
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <h2 class="vy-card-title" style="font-size:16px;margin-bottom:2px">Annual summaries</h2>
                    <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin:0 0 16px">Rental income and fees, ready for your accountant.</p>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        @forelse ($summaries as $summary)
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                                <a href="{{ route('owner.tax.annual', $summary['year']) }}" class="vy-tax-sumrow">
                                    <span class="vy-tax-sum-ic"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M9 13h6M9 17h4"/></svg></span>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $summary['year'] }}</div>
                                        <div class="vy-line-sub" style="font-size:11.5px;margin-top:2px">Net {{ $summary['net_display'] }} · gross {{ $summary['gross_display'] }}</div>
                                    </div>
                                    @if ($summary['in_progress'])
                                        <span class="vy-tax-sum-tag" style="color:var(--vy-muted)">Year to date</span>
                                    @else
                                        <span class="vy-tax-sum-tag" style="color:#3F6A38">PDF<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                                    @endif
                                </a>
                            @else
                                <div class="vy-tax-sumrow" style="cursor:default">
                                    <span class="vy-tax-sum-ic"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M9 13h6M9 17h4"/></svg></span>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $summary['year'] }}</div>
                                        <div class="vy-line-sub" style="font-size:11.5px;margin-top:2px">Net {{ $summary['net_display'] }} · gross {{ $summary['gross_display'] }}</div>
                                    </div>
                                </div>
                            @endownercan
                        @empty
                            <div class="vy-empty">No annual summaries yet — they appear once you have a finalized statement.</div>
                        @endforelse
                    </div>
                </section>

                {{-- VAT invoices & documents — the real Documents archive --}}
                <a href="{{ route('owner.documents') }}" class="vy-tax-docs vy-fade" style="animation-delay:.09s">
                    <span class="vy-tax-docs-ic" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2v2"/><path d="M4 7h16a1 1 0 0 1 1 1.2l-1.6 9A2 2 0 0 1 17.4 19H6.6a2 2 0 0 1-2-1.8L3 8.2A1 1 0 0 1 4 7z"/></svg></span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14px;font-weight:600;color:var(--vy-ink)">VAT invoices &amp; documents</div>
                        <div style="font-size:12px;color:var(--vy-ink);margin-top:2px">All statements and tax files</div>
                    </div>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B8A99D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                </a>

                {{-- About your tax — dark, honest (unchanged copy) --}}
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.12s">
                    <span aria-hidden="true" style="display:inline-grid;place-items:center;width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.14);color:#fff;margin-bottom:13px">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5h.01"/></svg>
                    </span>
                    <h2 class="vy-card-title" style="font-size:15px">About your tax</h2>
                    <p class="vy-line-sub" style="color:var(--vy-ink);font-size:12.5px;margin-top:8px;line-height:1.6">Vyrobel keeps records of your rental income and fees, but we don’t file taxes for you. Share your annual summary with a qualified accountant. Nothing here is tax advice.</p>
                </section>
            </div>
        </div>
    @endunless
</x-owner.shell>
