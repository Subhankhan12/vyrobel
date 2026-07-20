{{-- Owner Payout Method (Phase 2 · Sub-gate 5). The owner captures/manages bank account(s) for payouts. The full
     IBAN is encrypted + NEVER rendered — only the masked form (···· last4) + detected country. The live rail
     (Stripe Connect) is deferred: no fake "Verified"/micro-deposit/schedule. Tax details live on the Tax Center.
     Self-only throughout. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="payout-method" title="Payout method" :subtitle="'Where your payouts settle'" :viewer="$viewer">

    @if (session('payout_method_notice'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('payout_method_notice') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber, #b8860b);margin-bottom:16px">
            <div style="font-size:13px;color:var(--vy-ink)">{{ $errors->first() }}</div>
        </div>
    @endif

    @unless ($linked)
        <div class="vy-card vy-fade"><div class="vy-empty">No owner account is linked to your login yet.</div></div>
    @else
        {{-- Honest live-rail status — capturing an account does NOT enable an automated transfer yet --}}
        <div class="vy-card vy-fade" style="margin-bottom:18px">
            <div style="font-size:13px;color:var(--vy-ink)">
                Your payouts are sent by your account manager to your default account. <strong>Automated bank transfers are coming soon</strong> — for now, adding an account tells us where to send them.
            </div>
        </div>

        <div class="vy-cols" style="display:grid;grid-template-columns:1.4fr 1fr;gap:18px;align-items:start">

            {{-- LEFT — the accounts + the add form --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                <section class="vy-card vy-fade">
                    <h2 class="vy-card-title" style="margin-bottom:4px">Bank accounts</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">Choose where monthly payouts settle.</p>

                    @forelse ($methods as $m)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid var(--vy-hairline)">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">
                                    {{ $m['label'] }}
                                    @if ($m['is_default'])<span class="vy-status vy-status--paid" style="margin-left:6px"><span class="vy-dot"></span>Default</span>@endif
                                </div>
                                <div class="vy-line-sub" style="font-size:12px;margin-top:2px">{{ $m['holder'] }}</div>
                                <div class="vy-line-sub vy-mono" style="font-size:12px">IBAN {{ $m['masked'] }} · {{ $m['country'] }} · {{ $m['currency'] }}</div>
                            </div>
                            <div style="display:flex;gap:6px;flex-shrink:0">
                                @unless ($m['is_default'])
                                    <form method="POST" action="{{ route('owner.payout-method.default', $m['id']) }}" style="margin:0">
                                        @csrf
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 10px;font-size:12px">Set default</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('owner.payout-method.remove', $m['id']) }}" style="margin:0" onsubmit="return confirm('Remove this bank account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 10px;font-size:12px">Remove</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">No bank account added yet — add one below so we know where to send your payouts.</div>
                    @endforelse
                </section>

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

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
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
            </div>

            {{-- RIGHT — where payouts go + the tax link --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                <section class="vy-card vy-card--accent vy-fade">
                    <span class="vy-eyebrow">Payouts go to</span>
                    @php $default = collect($methods)->firstWhere('is_default', true); @endphp
                    @if ($default)
                        <div style="font-family:var(--vy-display);font-weight:700;font-size:16px;margin-top:4px">{{ $default['label'] }}</div>
                        <div class="vy-line-sub vy-mono" style="font-size:12.5px;margin-top:2px">IBAN {{ $default['masked'] }} · {{ $default['currency'] }}</div>
                    @else
                        <div class="vy-line-sub" style="margin-top:6px">No default account yet — add one on the left.</div>
                    @endif
                </section>

                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <div class="vy-eyebrow">Tax &amp; remittance</div>
                    <p class="vy-line-sub" style="font-size:12px;margin-top:6px">Your legal name, VAT number and tax residency are set in the <a href="{{ route('owner.tax') }}" style="color:var(--vy-accent)">Tax Center</a> — they print on your remittance advice and annual summary.</p>
                </section>
            </div>
        </div>
    @endunless
</x-owner.shell>
