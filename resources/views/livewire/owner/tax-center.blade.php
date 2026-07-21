{{-- Owner Tax Center (Phase 2 · Sub-gate 4). The owner's tax profile (self-only, tax_id + vat_number encrypted),
     the payouts genuinely HELD until a tax ID is on file, and real per-year summaries → the Annual Tax PDF.
     Withholding (no per-country engine) and the OTA brand are NOT rendered. Reads/writes are self-only.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="tax" title="Tax center" :subtitle="'Your tax details & summaries'" :viewer="$viewer">

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
        {{-- 🔒 SG3 — this is the account owner's SELF-ONLY tax profile (resolved by user_id). A sub-user
             (co-owner/accountant) has no owner row of their own, so tell them honestly it's the owner's —
             never a bare "not linked" that implies a broken account, and never another owner's data. --}}
        @ownersubuser
            <div class="vy-card vy-fade"><div class="vy-empty">The tax profile belongs to the account owner and is managed by them — it isn’t part of your team role.</div></div>
        @else
            <div class="vy-card vy-fade"><div class="vy-empty">No owner account is linked to your login yet.</div></div>
        @endownersubuser
    @else
        {{-- Held-payout banner — REAL: the owner's actual pending payouts, held until a tax ID is on file --}}
        @if ($held !== [])
            <div class="vy-card vy-card--accent vy-fade" style="margin-bottom:18px">
                <div style="font-family:var(--vy-display);font-weight:700;font-size:15px">Add your tax details to release held payouts</div>
                <div class="vy-line-sub" style="margin-top:6px;font-size:13px">
                    @foreach ($held as $h){{ $h['display'] }}@if (! $loop->last) · @endif @endforeach
                    is on hold until we have a valid tax ID on file. It takes about a minute — just complete the form below.
                </div>
            </div>
        @elseif ($complete)
            <div class="vy-card vy-fade" style="margin-bottom:18px;border-left:3px solid var(--vy-accent)">
                <div style="font-size:13.5px;color:var(--vy-ink)">Your tax details are complete — payouts are released as normal.</div>
            </div>
        @endif

        <div class="vy-cols" style="display:grid;grid-template-columns:1.4fr 1fr;gap:18px;align-items:start">

            {{-- LEFT — the tax profile form (self-only) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="margin-bottom:4px">Tax profile</h2>
                <p class="vy-line-sub" style="font-size:12px;margin:0 0 14px">Used on your annual summary and any invoices we issue on your behalf.</p>

                <form method="POST" action="{{ route('owner.tax.save') }}">
                    @csrf

                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="legal_name">Legal name</label>
                        <input id="legal_name" type="text" name="legal_name" value="{{ old('legal_name', $profile['legal_name']) }}" class="vy-input" autocomplete="name">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                        <div class="vy-field" style="margin:0">
                            <label class="vy-field-label" for="type">Registered as</label>
                            <select id="type" name="type" class="vy-input">
                                <option value="individual" @selected(old('type', $profile['type']) === 'individual')>Individual</option>
                                <option value="company" @selected(old('type', $profile['type']) === 'company')>Company</option>
                            </select>
                        </div>
                        <div class="vy-field" style="margin:0">
                            <label class="vy-field-label" for="tax_country">Tax residency</label>
                            @php $countries = ['Portugal', 'Ireland', 'Spain', 'France', 'United Kingdom', 'Germany', 'Italy', 'Netherlands']; @endphp
                            <select id="tax_country" name="tax_country" class="vy-input">
                                <option value="">—</option>
                                @foreach ($countries as $c)
                                    <option value="{{ $c }}" @selected(old('tax_country', $profile['tax_country']) === $c)>{{ $c }}</option>
                                @endforeach
                                @if ($profile['tax_country'] !== '' && ! in_array($profile['tax_country'], $countries, true))
                                    <option value="{{ $profile['tax_country'] }}" selected>{{ $profile['tax_country'] }}</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="tax_id">Tax ID / NIF <span style="color:var(--vy-accent)">*</span></label>
                        <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id', $profile['tax_id']) }}" placeholder="e.g. 501234567" class="vy-input" required>
                    </div>

                    <div class="vy-field" style="margin-bottom:14px">
                        <label class="vy-field-label" for="vat_number">VAT number (optional)</label>
                        <input id="vat_number" type="text" name="vat_number" value="{{ old('vat_number', $profile['vat_number']) }}" placeholder="e.g. PT501234567" class="vy-input">
                    </div>

                    {{-- 🔒 SG2 — an accountant may READ tax & documents but SAVING is an "Approval" (co-owner only);
                         the tax.save route is co-owner-gated too, so this hides a button that would 403. --}}
                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Save tax details</button>
                    @else
                        <p class="vy-line-sub" style="font-size:12px;margin:0">Read-only — a co-owner can update these tax details.</p>
                    @endownercan
                </form>
                <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px">Your tax ID and VAT number are encrypted at rest. Saving them releases any held payouts.</p>
            </section>

            {{-- RIGHT — annual summaries (real) + the disclaimer --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <h2 class="vy-card-title" style="margin-bottom:4px">Annual summaries</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">Rental income and fees, ready for your accountant.</p>

                    @forelse ($summaries as $summary)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid var(--vy-hairline)">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">
                                    {{ $summary['year'] }}@if ($summary['in_progress']) <span class="vy-line-sub" style="font-weight:400">· year to date</span>@endif
                                </div>
                                <div class="vy-line-sub" style="font-size:12px">Net {{ $summary['net_display'] }} · gross {{ $summary['gross_display'] }}</div>
                            </div>
                            <a href="{{ route('owner.tax.annual', $summary['year']) }}" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12px">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                PDF
                            </a>
                        </div>
                    @empty
                        <div class="vy-empty">No annual summaries yet — they appear once you have a finalized statement.</div>
                    @endforelse
                </section>

                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <div class="vy-eyebrow">About your tax</div>
                    <p class="vy-line-sub" style="font-size:12px;margin-top:6px">Vyrobel keeps records of your rental income and fees, but we don’t file taxes for you. Share your annual summary with a qualified accountant — nothing here is tax advice.</p>
                </section>
            </div>
        </div>
    @endunless
</x-owner.shell>
